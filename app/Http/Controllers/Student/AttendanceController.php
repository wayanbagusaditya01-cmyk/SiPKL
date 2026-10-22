<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load('placement.period');
        $attendances = $student->attendances()
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15);
        $period = $this->activePeriod($student);

        return view('students.attendance.index', compact('student', 'attendances', 'period'));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request);
        $period = $this->activePeriod($student);

        if (! $period) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Absensi hanya dapat diisi setelah penempatan dan periode PKL aktif tersedia.',
            ]);
        }

        $values = $request->validate([
            'attendance_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$period->start_date->format('Y-m-d'),
                'before_or_equal:'.$period->end_date->format('Y-m-d'),
            ],
            'status' => ['required', Rule::in(['Hadir', 'Izin'])],
            'time_in' => ['nullable', 'required_if:status,Hadir', 'date_format:H:i'],
            'time_out' => ['nullable', 'required_if:status,Hadir', 'date_format:H:i', 'after:time_in'],
            'note' => ['nullable', 'required_if:status,Izin', 'string', 'max:1000'],
        ]);

        if ($student->attendances()->whereDate('attendance_date', $values['attendance_date'])->exists()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Absensi untuk tanggal ini sudah tersimpan.',
            ]);
        }

        Attendance::create([
            'student_id' => $student->id,
            'attendance_date' => $values['attendance_date'],
            'status' => $values['status'],
            'time_in' => $values['status'] === 'Hadir' ? ($values['time_in'] ?? null) : null,
            'time_out' => $values['status'] === 'Hadir' ? ($values['time_out'] ?? null) : null,
            'note' => $values['note'] ?? null,
        ]);

        return redirect()->route('student.attendance.index')->with('success', 'Absensi berhasil disimpan.');
    }

    private function studentFor(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->ensureStudent();
    }

    private function activePeriod(Student $student): ?PklPeriod
    {
        $placement = $student->placement()->with('period')->first();

        if ($placement && $placement->status === 'Aktif') {
            if ($placement->period && $placement->period->status === 'Aktif') {
                return $placement->period;
            }

            $activePeriod = PklPeriod::where('status', 'Aktif')->first();
            if ($activePeriod) {
                if (! $placement->period_id) {
                    $placement->update(['period_id' => $activePeriod->id]);
                }

                return $activePeriod;
            }
        }

        $activePeriod = PklPeriod::where('status', 'Aktif')->first();
        if ($activePeriod && ! $placement) {
            $firstPlace = Place::first();
            if ($firstPlace) {
                $student->placement()->create([
                    'place_id' => $firstPlace->id,
                    'period_id' => $activePeriod->id,
                    'status' => 'Aktif',
                    'placed_at' => now()->toDateString(),
                ]);

                return $activePeriod;
            }
        }

        return $placement?->period;
    }
}
