<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Coordinator\CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\MentorController;
use App\Http\Controllers\Coordinator\PeriodController;
use App\Http\Controllers\Coordinator\PlaceController;
use App\Http\Controllers\Coordinator\PlacementController;
use App\Http\Controllers\Coordinator\StudentController;
use App\Http\Controllers\Mentor\MentorAttendanceController;
use App\Http\Controllers\Mentor\MentorDashboardController;
use App\Http\Controllers\Mentor\MentorGradeController;
use App\Http\Controllers\Mentor\MentorJournalController;
use App\Http\Controllers\Mentor\MentorProfileController;
use App\Http\Controllers\Mentor\MentorStudentController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\JournalController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\StudentPortalController;
use App\Http\Middleware\EnsurePreviewRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::get('/login', fn () => redirect()->route('login'));
Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/preview/reset', [PreviewController::class, 'reset'])->middleware('auth')->name('preview.reset');

Route::middleware(['auth', EnsurePreviewRole::class])->group(function () {
    Route::post('/preview/{role}/activate', [PreviewController::class, 'activateStudent'])->name('preview.siswa.activate');
    Route::get('/preview/{role}/{page?}', [PreviewController::class, 'show'])->name('preview.page');
    Route::post('/preview/{role}/{page}', [PreviewController::class, 'action'])->name('preview.action');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('accounts', AccountController::class);
    Route::post('/accounts/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
    Route::post('/accounts/{account}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');

    Route::get('/hak-akses', [PermissionController::class, 'index'])->name('permissions.index');
    Route::put('/hak-akses', [PermissionController::class, 'update'])->name('permissions.update');

    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::delete('/log-aktivitas/clear', [ActivityLogController::class, 'clear'])->name('logs.clear');

    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
    Route::post('/master-data', [MasterDataController::class, 'store'])->name('master-data.store');
    Route::put('/master-data/{reference}', [MasterDataController::class, 'update'])->name('master-data.update');
    Route::delete('/master-data/{reference}', [MasterDataController::class, 'destroy'])->name('master-data.destroy');

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    Route::get('/profil', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:koordinator'])->prefix('koordinator')->name('coordinator.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('coordinator.dashboard'));
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');
    Route::resource('siswa', StudentController::class)->parameters(['siswa' => 'student'])->except(['show', 'destroy']);
    Route::resource('tempat', PlaceController::class)->parameters(['tempat' => 'place'])->except(['show', 'destroy']);
    Route::resource('penempatan', PlacementController::class)->parameters(['penempatan' => 'placement'])->except(['show', 'destroy']);
    Route::resource('pembina', MentorController::class)->parameters(['pembina' => 'mentor'])->except(['show', 'destroy']);
    Route::resource('periode', PeriodController::class)->parameters(['periode' => 'period'])->except(['show', 'destroy']);
});

Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('student.')->group(function (): void {
    Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('portal.dashboard');
    Route::get('/penempatan', [StudentPortalController::class, 'placement'])->name('portal.placement');
    Route::get('/tempat-pkl', [StudentPortalController::class, 'place'])->name('portal.place');
    Route::get('/pembina', [StudentPortalController::class, 'mentors'])->name('portal.mentors');
    Route::get('/nilai', [StudentPortalController::class, 'grade'])->name('portal.grade');
    Route::get('/absensi', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/absensi', [AttendanceController::class, 'store'])->middleware('throttle:30,1')->name('attendance.store');
    Route::get('/jurnal', [JournalController::class, 'index'])->name('journals.index');
    Route::post('/jurnal', [JournalController::class, 'store'])->middleware('throttle:20,1')->name('journals.store');
    Route::put('/jurnal/{journal}', [JournalController::class, 'update'])->name('journals.update');
    Route::delete('/jurnal/{journal}', [JournalController::class, 'destroy'])->name('journals.destroy');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifikasi/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifikasi/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
});

Route::middleware(['auth', 'role:'.implode(',', User::MENTOR_ROLES)])->prefix('pembina')->name('mentor.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('mentor.dashboard'));
    Route::get('/dashboard', [MentorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/siswa', [MentorStudentController::class, 'index'])->name('students.index');
    Route::get('/penempatan', [MentorStudentController::class, 'placements'])->name('placements.index');
    Route::get('/absensi', [MentorAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/jurnal', [MentorJournalController::class, 'index'])->name('journals.index');
    Route::post('/jurnal/{journal}/setujui', [MentorJournalController::class, 'approve'])->name('journals.approve');
    Route::post('/jurnal/{journal}/revisi', [MentorJournalController::class, 'revise'])->name('journals.revise');
    Route::get('/penilaian', [MentorGradeController::class, 'index'])->name('grades.index');
    Route::post('/penilaian', [MentorGradeController::class, 'store'])->middleware('throttle:30,1')->name('grades.store');
    Route::get('/profil', [MentorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [MentorProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [MentorProfileController::class, 'updatePassword'])->name('profile.password');
});
