<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePreviewRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = $request->route('role');

        $roleMatches = ($user && $user->role === $role) ||
            ($user && $user->role === 'pembina' && in_array($role, ['pembina_sekolah', 'pembina_industri'])) ||
            ($role === 'pembina' && in_array($user?->role, ['pembina_sekolah', 'pembina_industri']));

        abort_unless(
            $user && $user->status && is_string($role) && $roleMatches,
            403,
        );

        return $next($request);
    }
}
