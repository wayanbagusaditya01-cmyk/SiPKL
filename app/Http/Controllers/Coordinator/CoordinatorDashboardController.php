<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use Illuminate\View\View;

class CoordinatorDashboardController extends Controller
{
    public function index(): View
    {
        $students = Student::query()
            ->with(['placement.place', 'placement.period', 'placement.schoolMentor.user', 'placement.industryMentor.user'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $unplacedStudents = $students->filter(fn (Student $student): bool => $student->placement === null)->values();

        $placements = Placement::query()
            ->with(['student', 'place', 'period', 'schoolMentor.user', 'industryMentor.user'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $activePeriod = PklPeriod::where('status', 'Aktif')->first();
        $placeCount = Place::count();
        $activePlacementCount = Placement::where('status', 'Aktif')->count();

        return view('coordinator.dashboard', [
            'students' => $students,
            'unplacedStudents' => $unplacedStudents,
            'placements' => $placements,
            'activePeriod' => $activePeriod,
            'placeCount' => $placeCount,
            'activePlacementCount' => $activePlacementCount,
        ]);
    }
}
