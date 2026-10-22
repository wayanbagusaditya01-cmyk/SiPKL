<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        RolePermission::ensureDefaults();

        $roles = config('preview.roles');
        $permissions = RolePermission::all();

        // Group matrix by module_key
        $modules = [];
        foreach (RolePermission::defaultMatrix() as $def) {
            $key = $def['module_key'];
            $modules[$key] = [
                'name' => $def['module_name'],
                'roles' => [],
            ];
            foreach (array_keys($roles) as $role) {
                $perm = $permissions->first(fn ($p) => $p->module_key === $key && $p->role === $role);
                $modules[$key]['roles'][$role] = $perm ? $perm->access_level : ($def['levels'][$role] ?? 'Tidak tersedia');
            }
        }

        return view('admin.permissions.index', [
            'roles' => $roles,
            'modules' => $modules,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*.*' => ['required', 'string', 'max:100'],
        ]);

        foreach ($validated['permissions'] as $moduleKey => $roleLevels) {
            foreach ($roleLevels as $role => $accessLevel) {
                RolePermission::updateOrCreate(
                    ['role' => $role, 'module_key' => $moduleKey],
                    ['access_level' => trim($accessLevel)]
                );
            }
        }

        ActivityLog::record(
            'Ubah Hak Akses',
            'Matriks Hak Akses',
            'Memperbarui konfigurasi hak akses modul untuk seluruh peran',
            $request->user()
        );

        return redirect()->route('admin.permissions.index')->with('success', 'Matriks hak akses berhasil diperbarui.');
    }
}
