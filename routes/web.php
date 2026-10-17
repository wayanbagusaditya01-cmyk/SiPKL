<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Coordinator\CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\MentorController;
use App\Http\Controllers\Coordinator\PeriodController;
use App\Http\Controllers\Coordinator\PlaceController;
use App\Http\Controllers\Coordinator\PlacementController;
use App\Http\Controllers\Coordinator\StudentController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\JournalController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\StudentPortalController;
use App\Http\Middleware\EnsurePreviewRole;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::get('/login', fn () => redirect()->route('login'));
Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/preview/reset', [PreviewController::class, 'reset'])->middleware('auth')->name('preview.reset');

Route::middleware(['auth', EnsurePreviewRole::class])->group(function () {
    Route::get('/preview/{role}/{page?}', [PreviewController::class, 'show'])->name('preview.page');
    Route::post('/preview/{role}/{page}', [PreviewController::class, 'action'])->name('preview.action');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('accounts', AccountController::class)->except(['show', 'destroy']);
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
