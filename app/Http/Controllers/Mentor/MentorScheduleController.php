<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Place;
use App\Models\PlaceHoliday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MentorScheduleController extends Controller
{
    use ResolvesMentor;

    public function edit(Request $request): View
    {
        $place = $this->managedPlace($request)->load('holidays');

        return view('mentor.schedule', ['place' => $place, 'dayNames' => Place::DAY_NAMES]);
    }

    public function update(Request $request): RedirectResponse
    {
        $place = $this->managedPlace($request);

        $values = $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'attendance_radius' => ['required', 'integer', 'between:25,2000'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', Rule::in(array_keys(Place::DAY_NAMES))],
            'check_in_start' => ['required', 'date_format:H:i'],
            'check_in_end' => ['required', 'date_format:H:i', 'after:check_in_start'],
            'check_out_start' => ['required', 'date_format:H:i', 'after:check_in_end'],
        ], [
            'work_days.required' => 'Pilih minimal satu hari kerja.',
            'check_in_end.after' => 'Batas tepat waktu harus setelah jam masuk dibuka.',
            'check_out_start.after' => 'Jam pulang harus setelah batas tepat waktu.',
        ]);

        $values['work_days'] = collect($values['work_days'])->map(fn ($day): int => (int) $day)->unique()->sort()->values()->all();
        $place->update($values);

        ActivityLog::record('Ubah Jadwal PKL', "Tempat PKL ({$place->name})", "Pembina Industri {$request->user()->name} memperbarui jadwal dan lokasi absensi {$place->name}");

        return redirect()->route('mentor.schedule.edit')->with('success', 'Jadwal dan lokasi absensi berhasil disimpan.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $place = $this->managedPlace($request);

        $values = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai libur tidak boleh sebelum tanggal mulai.',
        ]);

        $holiday = $place->holidays()->create($values);

        ActivityLog::record('Tambah Libur PKL', "Tempat PKL ({$place->name})", "Libur \"{$holiday->name}\" {$holiday->start_date->format('d-m-Y')} s.d. {$holiday->end_date->format('d-m-Y')}");

        return redirect()->route('mentor.schedule.edit')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroyHoliday(Request $request, PlaceHoliday $holiday): RedirectResponse
    {
        $place = $this->managedPlace($request);
        abort_unless($holiday->place_id === $place->id, 404);

        $holiday->delete();

        return redirect()->route('mentor.schedule.edit')->with('success', 'Hari libur berhasil dihapus.');
    }

    /**
     * Tempat PKL yang dikelola Pembina Industri yang sedang masuk.
     */
    private function managedPlace(Request $request): Place
    {
        $mentor = $this->mentorFor($request);
        abort_unless($mentor->isIndustry() && $mentor->place, 403, 'Jadwal hanya dapat diatur oleh Pembina Industri di tempat PKL-nya.');

        return $mentor->place;
    }
}
