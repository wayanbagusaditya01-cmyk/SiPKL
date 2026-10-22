<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorStudentController extends Controller
{
    use ResolvesMentor;

    public function index(Request $request): View
    {
        $mentor = $this->mentorFor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $placements = $mentor->guidedPlacements()
            ->with(['student', 'place', 'period', 'schoolMentor.user', 'industryMentor.user'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('student', function (Builder $query) use ($search): void {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('nis', 'like', '%'.$search.'%');
                    })->orWhereHas('place', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->get()
            ->sortBy(fn ($placement) => $placement->student?->name)
            ->values();

        $q = $filters['q'] ?? '';

        return view('mentor.students.index', compact('mentor', 'placements', 'q'));
    }

    public function placements(Request $request): View
    {
        $mentor = $this->mentorFor($request);

        $placements = $mentor->guidedPlacements()
            ->with(['student', 'place', 'period', 'schoolMentor.user', 'industryMentor.user'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();

        return view('mentor.placements.index', compact('mentor', 'placements'));
    }
}
