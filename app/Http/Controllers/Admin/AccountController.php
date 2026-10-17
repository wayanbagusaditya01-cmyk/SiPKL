<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys(config('preview.roles')))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.accounts.index', [
            'users' => $users,
            'roles' => config('preview.roles'),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.accounts.form', [
            'account' => null,
            'roles' => config('preview.roles'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(config('preview.roles')))],
            'status' => ['required', 'boolean'],
        ]);

        User::create([
            'name' => $values['name'],
            'email' => Str::lower(trim($values['email'])),
            'password' => $values['password'],
            'role' => $values['role'],
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $account): View
    {
        return view('admin.accounts.form', [
            'account' => $account,
            'roles' => config('preview.roles'),
        ]);
    }

    public function update(Request $request, User $account): RedirectResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account->id)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(config('preview.roles')))],
            'status' => ['required', 'boolean'],
        ]);

        if ($account->is($request->user()) && ($values['role'] !== 'admin' || ! $request->boolean('status'))) {
            throw ValidationException::withMessages([
                'status' => 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan atau diubah perannya.',
            ]);
        }

        $password = $values['password'] ?? null;
        $account->fill([
            'name' => $values['name'],
            'email' => Str::lower(trim($values['email'])),
            'role' => $values['role'],
            'status' => $request->boolean('status'),
        ]);

        if ($password !== null && $password !== '') {
            $account->password = $password;
        }

        $account->save();

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }
}
