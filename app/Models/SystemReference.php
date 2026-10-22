<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'code', 'name', 'manager_role', 'description', 'is_system'])]
class SystemReference extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public static function defaultReferences(): array
    {
        return [
            ['category' => 'Peran akun', 'code' => 'admin', 'name' => 'Admin', 'manager_role' => 'Admin', 'description' => 'Pengelola sistem, akun, hak akses, dan log', 'is_system' => true],
            ['category' => 'Peran akun', 'code' => 'koordinator', 'name' => 'Koordinator PKL', 'manager_role' => 'Admin', 'description' => 'Pengelola operasional, siswa, tempat, dan penempatan PKL', 'is_system' => true],
            ['category' => 'Peran akun', 'code' => 'pembina_sekolah', 'name' => 'Pembina Sekolah', 'manager_role' => 'Admin', 'description' => 'Guru pembimbing dari sekolah untuk monitoring, verifikasi jurnal, dan nilai', 'is_system' => true],
            ['category' => 'Peran akun', 'code' => 'pembina_industri', 'name' => 'Pembina Industri', 'manager_role' => 'Admin', 'description' => 'Pembimbing dari tempat PKL untuk monitoring, verifikasi jurnal, dan nilai', 'is_system' => true],
            ['category' => 'Peran akun', 'code' => 'siswa', 'name' => 'Siswa', 'manager_role' => 'Admin', 'description' => 'Peserta program PKL yang mengisi absensi dan jurnal', 'is_system' => true],

            ['category' => 'Status akun', 'code' => 'aktif', 'name' => 'Aktif', 'manager_role' => 'Admin', 'description' => 'Akun dapat masuk dan menggunakan sistem', 'is_system' => true],
            ['category' => 'Status akun', 'code' => 'nonaktif', 'name' => 'Nonaktif', 'manager_role' => 'Admin', 'description' => 'Akun ditangguhkan dan tidak dapat masuk', 'is_system' => true],
            ['category' => 'Status akun', 'code' => 'menunggu', 'name' => 'Menunggu', 'manager_role' => 'Admin', 'description' => 'Akun baru menunggu verifikasi/aktivasi profil', 'is_system' => true],

            ['category' => 'Status jurnal', 'code' => 'draf', 'name' => 'Draf', 'manager_role' => 'Admin', 'description' => 'Jurnal masih dalam proses pengisian oleh siswa', 'is_system' => true],
            ['category' => 'Status jurnal', 'code' => 'menunggu', 'name' => 'Menunggu', 'manager_role' => 'Admin', 'description' => 'Jurnal terkirim dan menunggu verifikasi pembina', 'is_system' => true],
            ['category' => 'Status jurnal', 'code' => 'disetujui', 'name' => 'Disetujui', 'manager_role' => 'Admin', 'description' => 'Jurnal telah disetujui oleh pembina', 'is_system' => true],
            ['category' => 'Status jurnal', 'code' => 'perlu_revisi', 'name' => 'Perlu Revisi', 'manager_role' => 'Admin', 'description' => 'Jurnal perlu perbaikan sesuai catatan pembina', 'is_system' => true],

            ['category' => 'Status nilai', 'code' => 'draf', 'name' => 'Draf', 'manager_role' => 'Admin', 'description' => 'Nilai belum diterbitkan untuk siswa', 'is_system' => true],
            ['category' => 'Status nilai', 'code' => 'terbit', 'name' => 'Terbit', 'manager_role' => 'Admin', 'description' => 'Nilai akhir sudah diterbitkan dan dapat dilihat siswa', 'is_system' => true],

            ['category' => 'Jurusan', 'code' => 'rpl', 'name' => 'Rekayasa Perangkat Lunak', 'manager_role' => 'Admin', 'description' => 'Kompetensi keahlian Rekayasa Perangkat Lunak', 'is_system' => false],
            ['category' => 'Jurusan', 'code' => 'tkj', 'name' => 'Teknik Komputer dan Jaringan', 'manager_role' => 'Admin', 'description' => 'Kompetensi keahlian Teknik Komputer dan Jaringan', 'is_system' => false],
            ['category' => 'Jurusan', 'code' => 'dkv', 'name' => 'Desain Komunikasi Visual', 'manager_role' => 'Admin', 'description' => 'Kompetensi keahlian Desain Komunikasi Visual', 'is_system' => false],
        ];
    }

    public static function ensureDefaults(): void
    {
        if (self::count() > 0) {
            return;
        }

        foreach (self::defaultReferences() as $item) {
            self::updateOrCreate(
                ['category' => $item['category'], 'code' => $item['code']],
                $item
            );
        }
    }
}
