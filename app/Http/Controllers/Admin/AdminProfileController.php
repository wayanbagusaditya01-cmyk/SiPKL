<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('admin.profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $values = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user->update([
            'name' => trim($values['name']),
            'email' => Str::lower(trim($values['email'])),
            'phone' => $values['phone'] ? trim($values['phone']) : null,
        ]);

        ActivityLog::record(
            'Ubah Profil',
            'Profil Admin',
            'Memperbarui data profil admin',
            $user
        );

        return redirect()->route('admin.profile.edit')->with('success', 'Profil admin berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->string('password')),
        ]);

        ActivityLog::record(
            'Ubah Kata Sandi',
            'Profil Admin',
            'Admin mengubah kata sandi akun',
            $user
        );

        return redirect()->route('admin.profile.edit')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
