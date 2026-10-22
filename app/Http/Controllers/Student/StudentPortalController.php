<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load(['placement.place', 'placement.period', 'placement.schoolMentor.user', 'placement.industryMentor.user']);
        $attendances = $student->attendances()->orderByDesc('attendance_date')->orderByDesc('id')->limit(5)->get();
        $recentJournals = $student->journals()->orderByDesc('activity_date')->orderByDesc('id')->limit(5)->get();
        $attendanceCount = $student->attendances()->count();
        $presentCount = $student->attendances()->where('status', 'Hadir')->count();
        $journalCount = $student->journals()->count();
        $waitingJournalsCount = $student->journals()->where('status', 'Menunggu')->count();
        $publishedGrade = $student->grade()->where('status', 'Terbit')->first();

        return view('students.dashboard', compact('student', 'attendances', 'recentJournals', 'attendanceCount', 'presentCount', 'journalCount', 'waitingJournalsCount', 'publishedGrade'));
    }

    public function placement(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load(['placement.place', 'placement.period', 'placement.schoolMentor.user', 'placement.industryMentor.user']);

        return view('students.placement', compact('student'));
    }

    public function place(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load('placement.place');

        return view('students.place', compact('student'));
    }

    public function mentors(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load(['placement.place', 'placement.schoolMentor.user', 'placement.industryMentor.user']);

        return view('students.mentors', compact('student'));
    }

    public function grade(Request $request): View
    {
        $student = $this->studentFor($request);
        $grade = $student->grade()->where('status', 'Terbit')->with('mentor.user')->first();

        return view('students.grade', compact('student', 'grade'));
    }

    private function studentFor(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->ensureStudent();
    }
}
