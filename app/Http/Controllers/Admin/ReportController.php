<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\JournalEntry;
use App\Models\Mentor;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        $roles = config('preview.roles');

        $accountReport = [];
        foreach ($roles as $roleKey => $roleData) {
            $users = User::where('role', $roleKey)->get();
            $accountReport[$roleKey] = [
                'name' => $roleData['name'],
                'total' => $users->count(),
                'active' => $users->where('status', true)->count(),
                'inactive' => $users->where('status', false)->count(),
            ];
        }

        $stats = [
            'totalAccounts' => User::count(),
            'activeAccounts' => User::where('status', true)->count(),
            'inactiveAccounts' => User::where('status', false)->count(),
            'totalLogs' => ActivityLog::count(),
            'totalRoles' => count($roles),
            'totalStudents' => Student::count(),
            'totalMentors' => Mentor::count(),
            'totalPlaces' => Place::count(),
            'totalPlacements' => Placement::count(),
            'activePlacements' => Placement::where('status', 'Aktif')->count(),
            'totalAttendances' => Attendance::count(),
            'hadirAttendances' => Attendance::where('status', 'Hadir')->count(),
            'izinAttendances' => Attendance::where('status', 'Izin')->count(),
            'totalJournals' => JournalEntry::count(),
            'approvedJournals' => JournalEntry::where('status', 'Disetujui')->count(),
            'pendingJournals' => JournalEntry::where('status', 'Menunggu')->count(),
        ];

        return view('admin.reports.index', [
            'roles' => $roles,
            'accountReport' => $accountReport,
            'stats' => $stats,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        ActivityLog::record(
            'Ekspor Laporan',
            'Laporan Sistem',
            'Mengunduh berkas laporan sistem dan akun dalam format CSV',
            $request->user()
        );

        $fileName = 'sipkl-laporan-akun-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Section 1: Ringkasan Penggunaan Sistem
            fputcsv($handle, ['=== LAPORAN PENGGUNAAN SISTEM SIPKL ===']);
            fputcsv($handle, ['Tanggal Unduh', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Total Akun Pengguna', User::count()]);
            fputcsv($handle, ['Akun Aktif', User::where('status', true)->count()]);
            fputcsv($handle, ['Akun Nonaktif', User::where('status', false)->count()]);
            fputcsv($handle, ['Total Siswa', Student::count()]);
            fputcsv($handle, ['Total Tempat PKL', Place::count()]);
            fputcsv($handle, ['Total Penempatan', Placement::count()]);
            fputcsv($handle, ['Total Absensi', Attendance::count()]);
            fputcsv($handle, ['Total Jurnal', JournalEntry::count()]);
            fputcsv($handle, []);

            // Section 2: Daftar Akun Pengguna
            fputcsv($handle, ['=== DAFTAR AKUN PENGGUNA ===']);
            fputcsv($handle, ['ID', 'Nama', 'Email', 'No Telepon', 'Peran', 'Status', 'Tanggal Dibuat']);

            $roles = config('preview.roles');
            $users = User::orderBy('role')->orderBy('name')->get();
            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? '-',
                    $roles[$user->role]['name'] ?? $user->role,
                    $user->status ? 'Aktif' : 'Nonaktif',
                    $user->created_at?->format('Y-m-d H:i') ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
