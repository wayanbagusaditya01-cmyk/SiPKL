<?php

namespace App\Support;

use App\Models\User;

/**
 * Menentukan lingkup bimbingan akun Pembina PKL dalam pratinjau.
 *
 * Pembina Sekolah dan Pembina Industri sama-sama berperan "pembina".
 * Perbedaannya ada pada tipe profil Mentor, sehingga lingkup data
 * (siswa, jurnal, absensi, penilaian) mengikuti kolom penempatan yang sesuai.
 */
class PreviewMentor
{
    public const SCHOOL = 'Sekolah';

    public const INDUSTRY = 'Industri';

    public static function type(?User $user): string
    {
        if ($user?->role === 'pembina_industri') {
            return self::INDUSTRY;
        }

        if ($user?->role === 'pembina_sekolah') {
            return self::SCHOOL;
        }

        return $user?->mentor?->type === self::INDUSTRY ? self::INDUSTRY : self::SCHOOL;
    }

    /**
     * @return array{type: string, key: string, id: int}
     */
    public static function scope(?User $user): array
    {
        return self::type($user) === self::INDUSTRY
            ? ['type' => self::INDUSTRY, 'key' => 'industry', 'id' => 6]
            : ['type' => self::SCHOOL, 'key' => 'school', 'id' => 5];
    }

    public static function guides(array $placement, array $scope): bool
    {
        return ($placement[$scope['key']] ?? null) === $scope['id'];
    }
}
