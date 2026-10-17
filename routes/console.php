<?php

use App\Models\Attendance;
use App\Models\JournalEntry;
use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sipkl:users', function () {
    $users = User::all(['id', 'name', 'email', 'role', 'status']);
    $this->table(['ID', 'Name', 'Email', 'Role', 'Status'], $users->toArray());
});

Artisan::command('sipkl:setup-coordinator', function () {
    $coordinator1 = User::updateOrCreate(
        ['email' => 'gazelle@sipkl.ti'],
        [
            'name' => 'Gazelle (Koordinator)',
            'password' => Hash::make('password123'),
            'role' => 'koordinator',
            'status' => true,
        ]
    );

    $coordinator2 = User::updateOrCreate(
        ['email' => 'koordinator@sipkl.ti'],
        [
            'name' => 'Koordinator PKL',
            'password' => Hash::make('password123'),
            'role' => 'koordinator',
            'status' => true,
        ]
    );

    $this->info('Koordinator users ready:');
    $this->line("- Email: {$coordinator1->email} | Password: password123 | Role: {$coordinator1->role}");
    $this->line("- Email: {$coordinator2->email} | Password: password123 | Role: {$coordinator2->role}");
});

Artisan::command('sipkl:coordinator-stats', function () {
    $this->info('--- DATABASE SIPKL STATS ---');
    $this->line('Students count: '.Student::count());
    $this->line('Places count: '.Place::count());
    $this->line('Mentors count: '.Mentor::count());
    $this->line('Periods count: '.PklPeriod::count());
    $this->line('Active Period: '.(PklPeriod::where('status', 'Aktif')->value('name') ?? 'None'));
    $this->line('Placements count: '.Placement::count());
    $this->line('Active Placements: '.Placement::where('status', 'Aktif')->count());
    $this->line('Attendance count: '.Attendance::count());
    $this->line('Journal entries count: '.JournalEntry::count());
});
