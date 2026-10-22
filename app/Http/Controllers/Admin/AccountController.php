<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
            'role' => ['nullable', Rule::in([...array_keys(config('preview.roles')), 'pembina'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->select(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at'])
            ->with(['student', 'mentor.place'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
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

    public function show(User $account): View
    {
        $account->load(['student.placement.place', 'mentor.place', 'activityLogs' => fn ($q) => $q->take(10)]);

        return view('admin.accounts.show', [
            'account' => $account,
            'roles' => config('preview.roles'),
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
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in([...array_keys(config('preview.roles')), 'pembina'])],
            'status' => ['required', 'boolean'],
        ]);

        $user = User::create([
            'name' => $values['name'],
            'email' => Str::lower(trim($values['email'])),
            'phone' => ! empty($values['phone']) ? trim($values['phone']) : null,
            'password' => $values['password'],
            'role' => $values['role'],
            'status' => $request->boolean('status'),
        ]);

        if ($user->role === 'siswa') {
            $user->ensureStudent();
        }

        ActivityLog::record(
            'Buat Akun',
            "Akun ({$user->email})",
            "Membuat akun baru {$user->name} dengan peran {$user->role} dan status ".($user->status ? 'Aktif' : 'Nonaktif'),
            $request->user()
        );

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
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in([...array_keys(config('preview.roles')), 'pembina'])],
            'status' => ['required', 'boolean'],
        ]);

        if ($account->is($request->user()) && ($values['role'] !== 'admin' || ! $request->boolean('status'))) {
            throw ValidationException::withMessages([
                'status' => 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan atau diubah perannya.',
            ]);
        }

        if ($account->mentor && $account->role !== $values['role']) {
            $changedAccount = (new User)->forceFill(['role' => $values['role']]);
            $expectedType = $changedAccount->expectedMentorType();

            if (! $changedAccount->isMentor() || ($expectedType !== null && $expectedType !== $account->mentor->type)) {
                throw ValidationException::withMessages([
                    'role' => "Akun ini sudah terhubung ke profil Pembina {$account->mentor->type}. Ubah profil pembinanya terlebih dahulu melalui Koordinator PKL.",
                ]);
            }
        }

        $password = $values['password'] ?? null;
        $account->fill([
            'name' => $values['name'],
            'email' => Str::lower(trim($values['email'])),
            'phone' => array_key_exists('phone', $values) ? ($values['phone'] ? trim($values['phone']) : null) : $account->phone,
            'role' => $values['role'],
            'status' => $request->boolean('status'),
        ]);

        if ($password !== null && $password !== '') {
            $account->password = $password;
        }

        $account->save();

        if ($account->role === 'siswa') {
            $account->ensureStudent();
        }

        ActivityLog::record(
            'Ubah Akun',
            "Akun ({$account->email})",
            "Memperbarui akun {$account->name} (Peran: {$account->role}, Status: ".($account->status ? 'Aktif' : 'Nonaktif').')',
            $request->user()
        );

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, User $account): RedirectResponse
    {
        if ($account->is($request->user())) {
            return back()->withErrors([
                'error' => 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan.',
            ]);
        }

        $newStatus = ! $account->status;
        $account->update(['status' => $newStatus]);

        ActivityLog::record(
            $newStatus ? 'Aktivasi Akun' : 'Nonaktivasi Akun',
            "Akun ({$account->email})",
            "Mengubah status akun {$account->name} menjadi ".($newStatus ? 'Aktif' : 'Nonaktif'),
            $request->user()
        );

        return back()->with('success', 'Status akun berhasil diubah menjadi '.($newStatus ? 'Aktif' : 'Nonaktif').'.');
    }

    public function resetPassword(Request $request, User $account): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $account->update([
            'password' => $request->string('password'),
        ]);

        ActivityLog::record(
            'Reset Kata Sandi',
            "Akun ({$account->email})",
            "Mereset kata sandi akun {$account->name}",
            $request->user()
        );

        return back()->with('success', 'Kata sandi akun '.$account->name.' berhasil direset.');
    }

    public function destroy(Request $request, User $account): RedirectResponse
    {
        if ($account->is($request->user())) {
            return back()->withErrors([
                'error' => 'Akun admin yang sedang digunakan tidak dapat dihapus.',
            ]);
        }

        $name = $account->name;
        $email = $account->email;

        // If user has linked student or mentor, decouple user_id to prevent orphaned delete constraint errors
        if ($account->student) {
            $account->student->update(['user_id' => null]);
        }
        if ($account->mentor) {
            $account->mentor->update(['user_id' => null]);
        }

        $account->delete();

        ActivityLog::record(
            'Hapus Akun',
            "Akun ({$email})",
            "Menghapus akun {$name} ({$email})",
            $request->user()
        );

        return redirect()->route('admin.accounts.index')->with('success', 'Akun '.$name.' berhasil dihapus.');
    }
}
