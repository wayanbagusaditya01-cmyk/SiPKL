<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
        $user = Auth::user();

        if ($user instanceof User && $user->status && $this->homeRouteFor($user) !== null) {
            return redirect()->route($this->homeRouteFor($user));
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

        $homeRoute = $this->homeRouteFor($user);

        if ($homeRoute === null) {
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

        if ($user->role === 'siswa') {
            $user->ensureStudent();
        }

        return redirect()->route($homeRoute);
    }

    /**
     * Halaman awal portal database untuk setiap peran, atau null bila peran tidak dikenal.
     */
    private function homeRouteFor(User $user): ?string
    {
        if ($user->isMentor()) {
            return 'mentor.dashboard';
        }

        return match ($user->role) {
            'admin' => 'admin.dashboard',
            'koordinator' => 'coordinator.dashboard',
            'siswa' => 'student.portal.dashboard',
            default => null,
        };
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
