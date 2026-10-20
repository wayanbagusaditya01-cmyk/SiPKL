<?php

return [
    'enabled' => env('SIPKL_PREVIEW', false),
    'today' => '2026-10-08',
    'roles' => [
        'siswa' => ['name' => 'Siswa', 'person' => 'Aditya Pratama', 'initials' => 'AP', 'description' => 'Catat kegiatan dan progres PKL.', 'menus' => ['dashboard' => 'Ringkasan', 'penempatan' => 'Penempatan Saya', 'tempat-pkl' => 'Detail Tempat PKL', 'pembina' => 'Pembina Saya', 'absensi' => 'Absensi', 'jurnal' => 'Jurnal Harian', 'nilai' => 'Nilai & Catatan']],
        'pembina' => ['name' => 'Pembina PKL', 'person' => 'Ratna Sari', 'initials' => 'RS', 'description' => 'Tinjau kegiatan siswa bimbingan.', 'menus' => ['dashboard' => 'Ringkasan', 'siswa' => 'Siswa Bimbingan', 'penempatan' => 'Penempatan', 'absensi' => 'Absensi', 'jurnal' => 'Jurnal', 'penilaian' => 'Penilaian', 'profil' => 'Profil Saya'],
            // Pembina Industri tetap peran "pembina"; tampilan mengikuti tipe profil Mentor.
            'variants' => [
                'Industri' => ['name' => 'Pembina Industri', 'person' => 'Dimas Prakoso', 'initials' => 'DP', 'description' => 'Dampingi siswa di tempat PKL.', 'menus' => ['dashboard' => 'Ringkasan', 'siswa' => 'Siswa Magang', 'penempatan' => 'Penempatan', 'absensi' => 'Absensi', 'jurnal' => 'Jurnal', 'penilaian' => 'Penilaian', 'profil' => 'Profil Saya']],
            ],
        ],
        'koordinator' => ['name' => 'Koordinator PKL', 'person' => 'Arif Budiman', 'initials' => 'AB', 'description' => 'Atur pelaksanaan dan penempatan.', 'menus' => ['dashboard' => 'Ringkasan', 'siswa' => 'Data Siswa', 'tempat' => 'Tempat PKL', 'pembina' => 'Data Pembina', 'periode' => 'Periode PKL', 'penempatan' => 'Penempatan', 'laporan' => 'Laporan PKL']],
        'admin' => ['name' => 'Admin', 'person' => 'Nadia Rahma', 'initials' => 'NR', 'description' => 'Kelola akun dan akses sistem.', 'menus' => ['dashboard' => 'Ringkasan', 'akun' => 'Akun Pengguna', 'hak-akses' => 'Hak Akses', 'log-aktivitas' => 'Log Aktivitas', 'master-data' => 'Master Data', 'laporan' => 'Laporan Sistem', 'profil' => 'Profil Admin']],
    ],
];
