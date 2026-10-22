<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class MentorProfileController extends Controller
{
    use ResolvesMentor;

    public function edit(Request $request): View
    {
        $mentor = $this->mentorFor($request)->load('place');
        $user = $request->user();

        return view('mentor.profile', compact('user', 'mentor'));
    }

    public function update(Request $request): RedirectResponse
    {
        $mentor = $this->mentorFor($request);
        $user = $request->user();

        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => [$mentor->isIndustry() ? 'required' : 'nullable', 'string', 'max:32'],
        ]);

        $phone = isset($values['phone']) ? trim($values['phone']) : null;

        $user->update([
            'name' => trim($values['name']),
            'email' => Str::lower(trim($values['email'])),
            'phone' => $phone,
        ]);
        $mentor->update(['phone' => $phone]);

        return redirect()->route('mentor.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $this->mentorFor($request);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->string('password')),
        ]);

        return redirect()->route('mentor.profile.edit')->with('success', 'Kata sandi berhasil diubah.');
    }
}
