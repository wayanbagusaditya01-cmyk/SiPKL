<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && config('preview.enabled') && app()->environment(['local', 'testing'])) {
            $user = Auth::user();

            if ($user instanceof User && $user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user instanceof User && $user->role === 'siswa' && $user->student()->exists()) {
                return redirect()->route('student.portal.dashboard');
            }

            if ($user instanceof User && $user->role === 'koordinator') {
                return redirect()->route('coordinator.dashboard');
            }

            return redirect()->route('preview.page', ['role' => $user->role, 'page' => 'dashboard']);
        }

        return view('preview.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = Str::lower(trim($credentials['email']));
        $credentials['status'] = true;

        if (! Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Email atau kata sandi salah atau akun tidak aktif.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (! array_key_exists($user->role, config('preview.roles'))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Akun belum memiliki peran yang valid. Hubungi Admin sekolah.',
            ])->onlyInput('email');
        }

        ActivityLog::record(
            'Login',
            'Sesi Pengguna',
            "Pengguna {$user->name} ({$user->email}) berhasil masuk ke sistem",
            $user
        );

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'siswa') {
            if (! $user->student()->exists()) {
                $existing = Student::whereNull('user_id')
                    ->where(function ($query) use ($user): void {
                        $query->where('email', $user->email)
                            ->orWhere('name', $user->name);
                    })
                    ->first();

                if ($existing) {
                    $existing->update(['user_id' => $user->id]);
                }
            }

            if ($user->student()->exists()) {
                return redirect()->route('student.portal.dashboard');
            }
        }

        if ($user->role === 'koordinator') {
            return redirect()->route('coordinator.dashboard');
        }

        return redirect()->route('preview.page', ['role' => $user->role, 'page' => 'dashboard']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            ActivityLog::record(
                'Logout',
                'Sesi Pengguna',
                "Pengguna {$user->name} ({$user->email}) keluar dari sistem",
                $user
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
