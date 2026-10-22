<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Support\GeoDistance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Akurasi GPS terburuk (meter) yang masih diterima untuk absen masuk/pulang.
     */
    public const MAX_GPS_ACCURACY = 100;

    public function index(Request $request): View
    {
        $student = $this->studentFor($request);
        $placement = $this->activePlacement($student);
        $period = $placement?->period;
        $place = $placement?->place;
        $now = now();
        $today = $now->copy()->startOfDay();

        $todayAttendance = $student->attendances()->whereDate('attendance_date', $today)->first();
        $attendances = $student->attendances()
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(10);

        [$state, $opensAt] = $this->todayState($period, $place, $todayAttendance, $now);

        $countedUntil = $todayAttendance ? $today : $today->copy()->subDay();
        $scheduledDays = $period && $place
            ? $place->workDaysBetween($period->start_date, min($countedUntil, $period->end_date))
            : 0;
        $presentCount = $student->attendances()->where('status', 'Hadir')->count();
        $summary = [
            'present' => $presentCount,
            'leave' => $student->attendances()->where('status', 'Izin')->count(),
            'rate' => $scheduledDays > 0 ? (int) round(min(100, $presentCount / $scheduledDays * 100)) : 0,
        ];

        return view('students.attendance.index', compact(
            'student', 'period', 'place', 'todayAttendance', 'attendances', 'summary', 'state', 'opensAt',
        ));
    }

    public function checkIn(Request $request): RedirectResponse|JsonResponse
    {
        $student = $this->studentFor($request);
        $place = $this->placeOpenToday($student);
        $now = now();

        if ($now->lt($place->scheduleAt($now, 'check_in_start'))) {
            throw ValidationException::withMessages(['attendance' => 'Absen masuk dibuka pukul '.$place->scheduleTime('check_in_start').'.']);
        }

        if ($now->gte($place->scheduleAt($now, 'check_out_start'))) {
            throw ValidationException::withMessages(['attendance' => 'Waktu absen masuk hari ini sudah lewat.']);
        }

        $location = $this->validatedLocation($request, $place);

        if ($student->attendances()->whereDate('attendance_date', $now->toDateString())->exists()) {
            throw ValidationException::withMessages(['attendance' => 'Absensi hari ini sudah tercatat.']);
        }

        $deadline = $place->scheduleAt($now, 'check_in_end');
        $lateMinutes = $now->gt($deadline) ? (int) $deadline->diffInMinutes($now) : null;

        Attendance::create([
            'student_id' => $student->id,
            'attendance_date' => $now->toDateString(),
            'status' => 'Hadir',
            'time_in' => $now->format('H:i'),
            'late_minutes' => $lateMinutes,
            'check_in_latitude' => $location['latitude'],
            'check_in_longitude' => $location['longitude'],
            'check_in_accuracy' => $location['accuracy'],
            'check_in_distance' => $location['distance'],
        ]);

        return $this->respond($request, 'Absen masuk tercatat pukul '.$now->format('H:i').($lateMinutes ? " (terlambat {$lateMinutes} menit)" : '').'.');
    }

    public function checkOut(Request $request): RedirectResponse|JsonResponse
    {
        $student = $this->studentFor($request);
        $place = $this->placeOpenToday($student);
        $now = now();
        $attendance = $student->attendances()->whereDate('attendance_date', $now->toDateString())->first();

        if (! $attendance?->awaitsCheckOut()) {
            throw ValidationException::withMessages([
                'attendance' => $attendance ? 'Absensi hari ini sudah selesai.' : 'Lakukan absen masuk terlebih dahulu.',
            ]);
        }

        if ($now->lt($place->scheduleAt($now, 'check_out_start'))) {
            throw ValidationException::withMessages(['attendance' => 'Absen pulang dibuka pukul '.$place->scheduleTime('check_out_start').'.']);
        }

        $location = $this->validatedLocation($request, $place);

        $attendance->update([
            'time_out' => $now->format('H:i'),
            'check_out_latitude' => $location['latitude'],
            'check_out_longitude' => $location['longitude'],
            'check_out_accuracy' => $location['accuracy'],
            'check_out_distance' => $location['distance'],
        ]);

        return $this->respond($request, 'Absen pulang tercatat pukul '.$now->format('H:i').'.');
    }

    public function leave(Request $request): RedirectResponse|JsonResponse
    {
        $student = $this->studentFor($request);
        $placement = $this->activePlacement($student);
        $period = $placement?->period;

        if (! $period) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Izin hanya dapat diajukan setelah penempatan dan periode PKL aktif tersedia.',
            ]);
        }

        $latestDate = min(now()->startOfDay(), $period->end_date)->format('Y-m-d');
        $values = $request->validate([
            'attendance_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$period->start_date->format('Y-m-d'),
                'before_or_equal:'.$latestDate,
            ],
            'note' => ['required', 'string', 'max:1000'],
            'leave_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'attendance_date.before_or_equal' => 'Izin hanya dapat diajukan untuk hari ini atau tanggal sebelumnya.',
            'attendance_date.after_or_equal' => 'Tanggal izin harus berada di dalam periode PKL.',
        ]);

        if (! $placement->place->isWorkDay(Carbon::parse($values['attendance_date']))) {
            throw ValidationException::withMessages(['attendance_date' => 'Tanggal ini bukan hari kerja atau sedang libur.']);
        }

        if ($student->attendances()->whereDate('attendance_date', $values['attendance_date'])->exists()) {
            throw ValidationException::withMessages(['attendance_date' => 'Absensi untuk tanggal ini sudah tersimpan.']);
        }

        Attendance::create([
            'student_id' => $student->id,
            'attendance_date' => $values['attendance_date'],
            'status' => 'Izin',
            'note' => trim($values['note']),
            'leave_photo_path' => $request->file('leave_photo')?->store('leave_photos', 'public'),
        ]);

        return $this->respond($request, 'Pengajuan izin berhasil disimpan.');
    }

    /**
     * Keadaan absensi hari ini untuk halaman siswa, beserta waktu tombol akan dibuka.
     *
     * @return array{0: string, 1: Carbon|null}
     */
    private function todayState(?PklPeriod $period, ?Place $place, ?Attendance $todayAttendance, Carbon $now): array
    {
        if (! $period || ! $place || ! $now->copy()->startOfDay()->betweenIncluded($period->start_date, $period->end_date)) {
            return ['unavailable', null];
        }

        if ($todayAttendance?->status === 'Izin') {
            return ['leave', null];
        }

        if ($todayAttendance && ! $todayAttendance->awaitsCheckOut()) {
            return ['done', null];
        }

        if (! $todayAttendance && $place->holidayOn($now)) {
            return ['holiday', null];
        }

        if (! $todayAttendance && ! $place->isWorkDay($now)) {
            return ['off-day', null];
        }

        if (! $place->hasCoordinates()) {
            return ['no-location', null];
        }

        if ($todayAttendance) {
            $opensAt = $place->scheduleAt($now, 'check_out_start');

            return $now->lt($opensAt) ? ['check-out-wait', $opensAt] : ['check-out', null];
        }

        $opensAt = $place->scheduleAt($now, 'check_in_start');

        if ($now->lt($opensAt)) {
            return ['check-in-wait', $opensAt];
        }

        return $now->gte($place->scheduleAt($now, 'check_out_start')) ? ['missed', null] : ['check-in', null];
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('student.attendance.index')->with('success', $message);
    }

    /**
     * @return array{latitude: float, longitude: float, accuracy: int, distance: int}
     */
    private function validatedLocation(Request $request, Place $place): array
    {
        $values = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:100000'],
        ], [
            'latitude.required' => 'Lokasi belum terbaca. Izinkan akses lokasi di browser.',
            'longitude.required' => 'Lokasi belum terbaca. Izinkan akses lokasi di browser.',
        ]);

        $accuracy = (int) ceil((float) $values['accuracy']);

        if ($accuracy > self::MAX_GPS_ACCURACY) {
            throw ValidationException::withMessages([
                'location' => "Akurasi lokasi ±{$accuracy} m terlalu rendah (maksimal ±".self::MAX_GPS_ACCURACY.' m). Aktifkan GPS lalu coba lagi di area terbuka.',
            ]);
        }

        $distance = (int) round(GeoDistance::meters(
            (float) $values['latitude'],
            (float) $values['longitude'],
            (float) $place->latitude,
            (float) $place->longitude,
        ));

        if ($distance > $place->attendance_radius) {
            throw ValidationException::withMessages([
                'location' => "Kamu berada {$distance} m dari {$place->name}. Absensi hanya dapat dilakukan dalam radius {$place->attendance_radius} m.",
            ]);
        }

        return [
            'latitude' => (float) $values['latitude'],
            'longitude' => (float) $values['longitude'],
            'accuracy' => $accuracy,
            'distance' => $distance,
        ];
    }

    /**
     * Tempat PKL siswa bila hari ini termasuk periode PKL, hari kerja, dan titik lokasinya sudah diatur.
     */
    private function placeOpenToday(Student $student): Place
    {
        $placement = $this->activePlacement($student);
        $period = $placement?->period;
        $now = now();

        if (! $period) {
            throw ValidationException::withMessages([
                'attendance' => 'Absensi hanya dapat diisi setelah penempatan dan periode PKL aktif tersedia.',
            ]);
        }

        if (! $now->copy()->startOfDay()->betweenIncluded($period->start_date, $period->end_date)) {
            throw ValidationException::withMessages(['attendance' => 'Hari ini berada di luar periode PKL.']);
        }

        $place = $placement->place;

        if ($holiday = $place->holidayOn($now)) {
            throw ValidationException::withMessages(['attendance' => "Hari ini libur: {$holiday->name}."]);
        }

        if (! $place->isWorkDay($now)) {
            throw ValidationException::withMessages(['attendance' => 'Hari ini bukan hari kerja PKL.']);
        }

        if (! $place->hasCoordinates()) {
            throw ValidationException::withMessages([
                'location' => 'Titik lokasi tempat PKL belum diatur. Hubungi Pembina Industri.',
            ]);
        }

        return $place;
    }

    /**
     * Penempatan aktif siswa beserta periode PKL-nya. Penempatan tanpa periode
     * dihubungkan ke periode yang sedang aktif.
     */
    private function activePlacement(Student $student): ?Placement
    {
        $placement = $student->placement()->with(['place.holidays', 'period'])->first();

        if (! $placement || $placement->status !== 'Aktif' || ! $placement->place) {
            return null;
        }

        if (! $placement->period || $placement->period->status !== 'Aktif') {
            $activePeriod = PklPeriod::where('status', 'Aktif')->first();

            if (! $activePeriod) {
                return null;
            }

            if (! $placement->period_id) {
                $placement->update(['period_id' => $activePeriod->id]);
            }

            $placement->setRelation('period', $activePeriod);
        }

        return $placement;
    }

    private function studentFor(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->ensureStudent();
    }
}
