<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isMentor(), 403);

        $mentor = $user->mentor;

        if (! $mentor) {
            return view('mentor.unlinked', compact('user'));
        }

        $placements = $mentor->guidedPlacements()->get(['id', 'student_id', 'status']);
        $studentIds = $placements->pluck('student_id')->all();

        $recentAttendances = Attendance::whereIn('student_id', $studentIds)
            ->with('student')
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentJournals = JournalEntry::whereIn('student_id', $studentIds)
            ->where('status', '!=', 'Draf')
            ->with('student')
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('mentor.dashboard', [
            'mentor' => $mentor,
            'studentCount' => count($studentIds),
            'activePlacementCount' => $placements->where('status', 'Aktif')->count(),
            'recentAttendances' => $recentAttendances,
            'recentJournals' => $recentJournals,
            'pendingJournalCount' => JournalEntry::whereIn('student_id', $studentIds)->where('status', 'Menunggu')->count(),
            'gradedCount' => $mentor->grades()->whereIn('student_id', $studentIds)->count(),
        ]);
    }
}
