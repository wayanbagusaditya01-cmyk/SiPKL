<?php

namespace App\Support;

class PreviewData
{
    public static function initial(): array
    {
        return [
            'accounts' => [
                ['id' => 1, 'name' => 'Aditya Pratama', 'username' => 'aditya.contoh', 'role' => 'Siswa', 'status' => 'Aktif', 'profile' => true],
                ['id' => 2, 'name' => 'Kirana Putri', 'username' => 'kirana.contoh', 'role' => 'Siswa', 'status' => 'Aktif', 'profile' => true],
                ['id' => 3, 'name' => 'Bima Saputra', 'username' => 'bima.contoh', 'role' => 'Siswa', 'status' => 'Aktif', 'profile' => true],
                ['id' => 4, 'name' => 'Nayla Maharani', 'username' => 'nayla.contoh', 'role' => 'Siswa', 'status' => 'Aktif', 'profile' => true],
                ['id' => 5, 'name' => 'Ratna Sari', 'username' => 'ratna.contoh', 'role' => 'Pembina PKL', 'status' => 'Aktif', 'profile' => true],
                ['id' => 6, 'name' => 'Dimas Prakoso', 'username' => 'dimas.contoh', 'role' => 'Pembina PKL', 'status' => 'Aktif', 'profile' => true],
                ['id' => 7, 'name' => 'Arif Budiman', 'username' => 'arif.contoh', 'role' => 'Koordinator PKL', 'status' => 'Aktif', 'profile' => true],
                ['id' => 8, 'name' => 'Nadia Rahma', 'username' => 'nadia.contoh', 'role' => 'Admin', 'status' => 'Aktif', 'profile' => true],
                ['id' => 9, 'name' => 'Siswa baru', 'username' => 'siswa.baru', 'role' => 'Siswa', 'status' => 'Menunggu', 'profile' => false],
                ['id' => 10, 'name' => 'Pembina baru', 'username' => 'pembina.baru', 'role' => 'Pembina PKL', 'status' => 'Aktif', 'profile' => false],
                ['id' => 11, 'name' => 'Akun arsip', 'username' => 'arsip.contoh', 'role' => 'Siswa', 'status' => 'Nonaktif', 'profile' => true],
            ],
            'students' => [
                ['id' => 1, 'account' => 1, 'nis' => '2026001', 'name' => 'Aditya Pratama', 'class' => 'XII RPL 1', 'major' => 'Rekayasa Perangkat Lunak', 'email' => 'aditya@example.test'],
                ['id' => 2, 'account' => 2, 'nis' => '2026002', 'name' => 'Kirana Putri', 'class' => 'XII RPL 1', 'major' => 'Rekayasa Perangkat Lunak', 'email' => 'kirana@example.test'],
                ['id' => 3, 'account' => 3, 'nis' => '2026003', 'name' => 'Bima Saputra', 'class' => 'XII TKJ 2', 'major' => 'Teknik Komputer dan Jaringan', 'email' => 'bima@example.test'],
                ['id' => 4, 'account' => 4, 'nis' => '2026004', 'name' => 'Nayla Maharani', 'class' => 'XII DKV 1', 'major' => 'Desain Komunikasi Visual', 'email' => 'nayla@example.test'],
            ],
            'places' => [
                ['id' => 1, 'name' => 'Studio Nusa Digital', 'address' => 'Jl. Pendidikan No. 12, Makassar, Sulawesi Selatan', 'latitude' => '-5.147665', 'longitude' => '119.432732', 'contact' => 'Kontak contoh belum diisi', 'email' => 'studio@example.test'],
                ['id' => 2, 'name' => 'Laboratorium Teknologi Sekolah dan Pusat Pengembangan Kreatif', 'address' => 'Jl. Perintis Kemerdekaan, kompleks pendidikan, gedung laboratorium lantai 2, Makassar', 'latitude' => '-5.140000', 'longitude' => '119.480000', 'contact' => 'Kontak contoh belum diisi', 'email' => 'lab@example.test'],
            ],
            'mentors' => [
                ['id' => 5, 'account' => 5, 'name' => 'Ratna Sari', 'type' => 'Sekolah', 'position' => 'Guru pembimbing', 'nip' => '', 'phone' => '', 'email' => 'ratna@example.test', 'place' => null],
                ['id' => 6, 'account' => 6, 'name' => 'Dimas Prakoso', 'type' => 'Industri', 'position' => 'Supervisor', 'nip' => '', 'phone' => 'Kontak contoh belum diisi', 'email' => 'dimas@example.test', 'place' => 1],
            ],
            'periods' => [
                ['id' => 1, 'name' => 'PKL 2026', 'year' => '2026/2027', 'start' => '2026-08-01', 'end' => '2026-10-31', 'status' => 'Aktif'],
                ['id' => 2, 'name' => 'PKL 2025', 'year' => '2025/2026', 'start' => '2025-08-01', 'end' => '2025-10-31', 'status' => 'Selesai'],
            ],
            'placements' => [
                ['id' => 1, 'student' => 1, 'place' => 1, 'period' => 1, 'school' => 5, 'industry' => 6, 'date' => '2026-08-01', 'status' => 'Aktif'],
                ['id' => 2, 'student' => 2, 'place' => 1, 'period' => 1, 'school' => 5, 'industry' => 6, 'date' => '2026-08-01', 'status' => 'Aktif'],
            ],
            'attendance' => [
                ['id' => 1, 'student' => 1, 'date' => '2026-10-07', 'status' => 'Hadir', 'in' => '08:00', 'out' => '16:00', 'note' => ''],
                ['id' => 2, 'student' => 1, 'date' => '2026-10-06', 'status' => 'Hadir', 'in' => '08:05', 'out' => '16:00', 'note' => ''],
                ['id' => 3, 'student' => 1, 'date' => '2026-10-05', 'status' => 'Izin', 'in' => '', 'out' => '', 'note' => 'Keperluan keluarga'],
                ['id' => 4, 'student' => 2, 'date' => '2026-10-08', 'status' => 'Hadir', 'in' => '08:00', 'out' => '16:00', 'note' => ''],
            ],
            'journals' => [
                ['id' => 1, 'student' => 1, 'date' => '2026-10-08', 'title' => 'Menguji formulir absensi', 'activity' => 'Hari ini saya menguji validasi formulir absensi, memeriksa urutan jam masuk dan keluar, serta mencatat hasil pengujian. Saya belajar menyusun skenario uji yang dapat diulang.', 'url' => '', 'status' => 'Menunggu', 'note' => ''],
                ['id' => 2, 'student' => 2, 'date' => '2026-10-07', 'title' => 'Inventaris perangkat', 'activity' => 'Mencatat inventaris perangkat kerja dan memeriksa kelengkapan label aset. Saya belajar mengelompokkan perangkat berdasarkan fungsi.', 'url' => '', 'status' => 'Menunggu', 'note' => ''],
                ['id' => 3, 'student' => 1, 'date' => '2026-10-06', 'title' => 'Menyusun dokumentasi', 'activity' => 'Menyusun dokumentasi antarmuka aplikasi.', 'url' => '', 'status' => 'Perlu Revisi', 'note' => 'Tambahkan langkah pengujian dan pembelajaran yang kamu dapatkan.'],
                ['id' => 4, 'student' => 1, 'date' => '2026-10-03', 'title' => 'Riset kebutuhan pengguna', 'activity' => 'Mewawancarai pengguna bersama pembina industri dan membuat rangkuman kebutuhan aplikasi.', 'url' => '', 'status' => 'Disetujui', 'note' => 'Rangkuman jelas. Pertahankan kebiasaan mencatat hasil.'],
            ],
            'grades' => [],
            'logs' => [
                ['id' => 1, 'time' => '2026-10-08 09:12', 'actor' => 'nadia.contoh', 'role' => 'Admin', 'action' => 'Buat akun', 'entity' => 'Akun', 'detail' => 'Akun siswa.baru berstatus Menunggu. Profil belum terhubung.'],
                ['id' => 2, 'time' => '2026-10-08 08:46', 'actor' => 'ratna.contoh', 'role' => 'Pembina PKL', 'action' => 'Validasi jurnal', 'entity' => 'Jurnal', 'detail' => 'Jurnal kegiatan riset telah disetujui.'],
            ],
            'profiles' => [],
        ];
    }
}
