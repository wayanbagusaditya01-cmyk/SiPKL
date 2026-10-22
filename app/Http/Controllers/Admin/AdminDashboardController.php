<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Mentor;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $totalAccounts = User::count();
        $activeAccounts = User::where('status', true)->count();
        $inactiveAccounts = User::where('status', false)->count();

        $roles = config('preview.roles');
        $accountsPerRole = [];
        foreach ($roles as $roleKey => $roleData) {
            $accountsPerRole[$roleKey] = [
                'name' => $roleData['name'],
                'total' => User::where('role', $roleKey)->count(),
                'active' => User::where('role', $roleKey)->where('status', true)->count(),
                'inactive' => User::where('role', $roleKey)->where('status', false)->count(),
            ];
        }

        // Accounts that need follow-up (unlinked profiles or inactive accounts)
        $unlinkedStudents = User::where('role', 'siswa')
            ->doesntHave('student')
            ->take(5)
            ->get();

        $unlinkedMentors = User::whereIn('role', ['pembina', 'pembina_sekolah', 'pembina_industri'])
            ->doesntHave('mentor')
            ->take(5)
            ->get();

        $inactiveUsers = User::where('status', false)
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $latestLogs = ActivityLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        $stats = [
            'totalAccounts' => $totalAccounts,
            'activeAccounts' => $activeAccounts,
            'inactiveAccounts' => $inactiveAccounts,
            'totalStudents' => Student::count(),
            'totalMentors' => Mentor::count(),
            'totalPlaces' => Place::count(),
            'totalPlacements' => Placement::count(),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'roles',
            'accountsPerRole',
            'unlinkedStudents',
            'unlinkedMentors',
            'inactiveUsers',
            'latestLogs'
        ));
    }
}
