<?php

namespace App\Http\Controllers\Mentor;

use App\Models\Mentor;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesMentor
{
    /**
     * Ambil profil Pembina milik akun yang sedang masuk. Akun yang belum
     * terhubung ke profil diarahkan ke ringkasan yang menjelaskan langkahnya.
     */
    protected function mentorFor(Request $request): Mentor
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isMentor(), 403);

        $mentor = $user->mentor;

        if (! $mentor) {
            abort(redirect()->route('mentor.dashboard'));
        }

        return $mentor;
    }
}
