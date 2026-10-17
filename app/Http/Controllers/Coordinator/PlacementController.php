<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlacementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $placements = Placement::query()
            ->with([
                'student:id,nis,name,class',
                'place:id,name',
                'period:id,name,academic_year',
                'schoolMentor.user:id,name',
                'industryMentor.user:id,name',
            ])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('student', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%');
                })->orWhereHas('place', fn (Builder $query): Builder => $query->where('name', 'like', '%'.$search.'%'));
            })
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.placements.index', compact('placements', 'filters'));
    }

    public function create(): View
    {
        return view('coordinator.placements.form', [
            'placement' => null,
            'students' => $this->availableStudents(),
            'places' => Place::query()->orderBy('name')->get(['id', 'name']),
            'periods' => $this->availablePeriods(),
            'schoolMentors' => $this->mentorOptions('Sekolah'),
            'industryMentors' => $this->mentorOptions('Industri'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Placement::create($this->validatedValues($request));

        return redirect()->route('coordinator.penempatan.index')->with('success', 'Penempatan siswa berhasil disimpan.');
    }

    public function edit(Placement $placement): View
    {
        return view('coordinator.placements.form', [
            'placement' => $placement,
            'students' => $this->availableStudents($placement),
            'places' => Place::query()->orderBy('name')->get(['id', 'name']),
            'periods' => $this->availablePeriods($placement),
            'schoolMentors' => $this->mentorOptions('Sekolah'),
            'industryMentors' => $this->mentorOptions('Industri'),
        ]);
    }

    public function update(Request $request, Placement $placement): RedirectResponse
    {
        $placement->update($this->validatedValues($request, $placement));

        return redirect()->route('coordinator.penempatan.index')->with('success', 'Penempatan siswa berhasil diperbarui.');
    }

    private function validatedValues(Request $request, ?Placement $placement = null): array
    {
        $studentRule = Rule::unique('placements', 'student_id')->ignore($placement?->id);
        $periodRule = Rule::exists('pkl_periods', 'id');

        if (! $placement) {
            $periodRule->where('status', 'Aktif');
        } elseif ($placement->period_id) {
            $periodRule->where(fn ($query) => $query->where('status', 'Aktif')->orWhere('id', $placement->period_id));
        }

        $values = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id'), $studentRule],
            'place_id' => ['required', 'integer', Rule::exists('places', 'id')],
            'period_id' => ['required', 'integer', $periodRule],
            'school_mentor_id' => [
                'required',
                'integer',
                Rule::exists('mentors', 'id')->where(fn ($query) => $query->where('type', 'Sekolah')),
            ],
            'industry_mentor_id' => [
                'required',
                'integer',
                Rule::exists('mentors', 'id')->where(fn ($query) => $query->where('type', 'Industri')->where('place_id', $request->input('place_id'))),
            ],
            'placed_at' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['Aktif', 'Selesai'])],
        ]);

        $period = PklPeriod::findOrFail($values['period_id']);
        $placedAt = $values['placed_at'] ?? null;
        $periodStart = substr((string) $period->getRawOriginal('start_date'), 0, 10);
        $periodEnd = substr((string) $period->getRawOriginal('end_date'), 0, 10);

        if ($placedAt && ($placedAt < $periodStart || $placedAt > $periodEnd)) {
            throw ValidationException::withMessages([
                'placed_at' => 'Tanggal penempatan harus berada dalam rentang periode PKL.',
            ]);
        }

        return $values;
    }

    private function availableStudents(?Placement $placement = null): Collection
    {
        return Student::query()
            ->where(function (Builder $query) use ($placement): void {
                $query->whereDoesntHave('placement');

                if ($placement) {
                    $query->orWhereKey($placement->student_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'class']);
    }

    private function availablePeriods(?Placement $placement = null): Collection
    {
        return PklPeriod::query()
            ->where(function (Builder $query) use ($placement): void {
                $query->where('status', 'Aktif');

                if ($placement?->period_id) {
                    $query->orWhereKey($placement->period_id);
                }
            })
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'academic_year', 'start_date', 'end_date']);
    }

    private function mentorOptions(string $type): Collection
    {
        return Mentor::query()
            ->with(['user:id,name,email', 'place:id,name'])
            ->where('type', $type)
            ->orderBy('id')
            ->get();
    }
}
