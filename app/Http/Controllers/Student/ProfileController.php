<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load(['placement.place', 'placement.period', 'placement.schoolMentor.user', 'placement.industryMentor.user']);
        $user = $request->user();

        return view('students.profile', compact('student', 'user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request);
        $user = $request->user();

        $values = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $email = $values['email'] ? Str::lower(trim($values['email'])) : null;
        $name = trim($values['name']);

        $student->update([
            'name' => $name,
            'email' => $email,
        ]);

        if ($user instanceof User) {
            $user->update([
                'name' => $name,
            ]);
        }

        return redirect()->route('student.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->string('password')),
        ]);

        return redirect()->route('student.profile.edit')->with('success', 'Kata sandi berhasil diubah.');
    }

    private function studentFor(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->ensureStudent();
    }
}
