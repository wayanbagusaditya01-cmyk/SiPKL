<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['role', 'module_key', 'module_name', 'access_level', 'description'])]
class RolePermission extends Model
{
    use HasFactory;

    /**
     * Default role permissions definition conforming to the SIPKL scope.
     *
     * @return array<int, array{module_key: string, module_name: string, levels: array<string, string>}>
     */
    public static function defaultMatrix(): array
    {
        return [
            [
                'module_key' => 'accounts',
                'module_name' => 'Akun & akses',
                'levels' => [
                    'admin' => 'Kelola',
                    'koordinator' => 'Tidak tersedia',
                    'pembina' => 'Tidak tersedia',
                    'siswa' => 'Tidak tersedia',
                ],
            ],
            [
                'module_key' => 'operational',
                'module_name' => 'Data operasional',
                'levels' => [
                    'admin' => 'Tidak tersedia',
                    'koordinator' => 'Kelola',
                    'pembina' => 'Baca bimbingan',
                    'siswa' => 'Baca milik sendiri',
                ],
            ],
            [
                'module_key' => 'placements',
                'module_name' => 'Penempatan',
                'levels' => [
                    'admin' => 'Tidak tersedia',
                    'koordinator' => 'Kelola',
                    'pembina' => 'Baca bimbingan',
                    'siswa' => 'Baca milik sendiri',
                ],
            ],
            [
                'module_key' => 'attendance',
                'module_name' => 'Absensi',
                'levels' => [
                    'admin' => 'Tidak tersedia',
                    'koordinator' => 'Pantau',
                    'pembina' => 'Pantau bimbingan',
                    'siswa' => 'Isi milik sendiri',
                ],
            ],
            [
                'module_key' => 'journals',
                'module_name' => 'Jurnal',
                'levels' => [
                    'admin' => 'Tidak tersedia',
                    'koordinator' => 'Pantau',
                    'pembina' => 'Verifikasi bimbingan',
                    'siswa' => 'Isi milik sendiri',
                ],
            ],
            [
                'module_key' => 'grades',
                'module_name' => 'Nilai',
                'levels' => [
                    'admin' => 'Tidak tersedia',
                    'koordinator' => 'Pantau',
                    'pembina' => 'Kelola bimbingan',
                    'siswa' => 'Baca hasil terbit',
                ],
            ],
            [
                'module_key' => 'audit',
                'module_name' => 'Audit & konfigurasi',
                'levels' => [
                    'admin' => 'Kelola / baca audit',
                    'koordinator' => 'Tidak tersedia',
                    'pembina' => 'Tidak tersedia',
                    'siswa' => 'Tidak tersedia',
                ],
            ],
        ];
    }

    public static function ensureDefaults(): void
    {
        if (self::count() > 0) {
            return;
        }

        foreach (self::defaultMatrix() as $module) {
            foreach ($module['levels'] as $role => $accessLevel) {
                self::updateOrCreate(
                    ['role' => $role, 'module_key' => $module['module_key']],
                    [
                        'module_name' => $module['module_name'],
                        'access_level' => $accessLevel,
                        'description' => "Hak akses {$role} untuk {$module['module_name']}",
                    ]
                );
            }
        }
    }
}
