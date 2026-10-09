# SIPKL UI

Implementasi antarmuka Laravel dan Blade untuk Sistem Informasi Praktik Kerja Lapangan. Sumber kebutuhan adalah diagram V2, ERD, wireframe empat peran, dokumen skenario, dan brief pengguna. Bahan asli di `../Referensi` tidak diubah. Prototype halaman utama sebelumnya tetap berada di `../Prototype`.

## Menjalankan

Folder aplikasi adalah `SIPKL`. PHP yang tersedia saat implementasi adalah 8.5.9. Composer memasang Laravel 13.35.0 dan mengunci versi dalam `composer.lock`. Vite 8.3.3 dikunci dalam `package-lock.json`. Database produksi belum dipilih dan tidak diperlukan untuk pratinjau.

```powershell
cd 'C:\Users\BAGUS\Downloads\Tugas website PKL\SIPKL'
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Pada `.env`, ubah `SIPKL_PREVIEW=false` menjadi `SIPKL_PREVIEW=true` dan pertahankan `APP_ENV=local`. Selanjutnya:

```powershell
npm ci --ignore-scripts
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Buka [pratinjau lokal](http://127.0.0.1:8000). Pada workspace saat ini dependency, `.env`, application key, dan build sudah disiapkan. Tidak perlu menimpa `.env` yang sudah ada untuk menjalankan ulang.

Jika Composer membutuhkan ZIP pada PHP XAMPP yang tersedia, aktifkan hanya untuk proses instalasi:

```powershell
php -d extension=zip C:\ProgramData\ComposerSetup\bin\composer.phar install
```

Tidak ada migrasi database yang perlu dijalankan. Template tidak memakai CDN, font eksternal, React, Livewire, atau Tailwind. CSS memakai token terpusat dan diproses melalui Vite. Font Plus Jakarta Sans memakai fallback system-ui dan Segoe UI bila tidak tersedia lokal.

## Yang tersedia

- Halaman masuk dengan empat radio peran dan contoh pesan keadaan login. Input kredensial serta tombol Masuk dinonaktifkan. Tombol Buka Pratinjau membuka data contoh tanpa autentikasi.
- Empat dashboard dan 28 halaman menu sesuai peran, dengan shell Blade bersama.
- Siswa: penempatan, lokasi, pembina, absensi, draf jurnal, pengiriman, revisi dan kirim ulang, nilai terbit, serta keadaan tanpa penempatan.
- Pembina: siswa bimbingan, detail penempatan, pemantauan absensi, antrean jurnal dengan detail, persetujuan atau revisi, draf dan penerbitan nilai, serta profil.
- Koordinator: daftar dan form siswa, tempat, pembina, periode, dan penempatan dengan pembina industri sesuai tempat serta dialog ringkasan, ditambah laporan progres.
- Admin: akun contoh, perubahan status dengan konfirmasi, profil, matriks izin, nilai referensi sistem, log baca saja, dan ringkasan laporan.
- Pencarian, filter yang relevan, pagination akun, drawer mobile dengan focus trap, Escape, penguncian scroll, pengembalian fokus, dialog konfirmasi, pesan validasi Indonesia, dan feedback pengiriman.

Form aktif memakai POST Laravel, CSRF, validasi server, dan session file yang terpisah dari database. Data contoh berada di `app/Support/PreviewData.php`, katalog form dan tabel di `PreviewCatalog.php`. Simulasi dapat dibuka bergantian melalui Ganti Peran untuk memeriksa perubahan. Reset tersedia di Tentang Pratinjau. Data contoh dalam browser lain memiliki sesi sendiri.

## Batas integrasi

Ini adalah pratinjau UI, bukan aplikasi produksi. Pemilihan peran hanya memilih contoh layar. Tidak ada kredensial, login nyata, policy atau gate produksi, hashing akun, pengiriman email, upload dokumentasi, ekspor PDF atau CSV, ataupun perubahan izin produksi. Tombol yang belum terhubung dinonaktifkan dengan alasan di halaman. Kontak dan alamat email contoh tidak ditautkan sebagai penerima pesan nyata.

Route pratinjau memberikan 404 ketika `SIPKL_PREVIEW=false`, atau ketika environment bukan local/testing, termasuk production. Pengamanan lingkup pada data contoh bukan pengganti otorisasi produksi. Sebelum integrasi, perlu menetapkan versi target deployment, database, skema, profil akun, autentikasi dan otorisasi, serta aturan koreksi absensi dan status penerbitan. Jangan menyalin route demo menjadi route produksi.

## Referensi dan keputusan

Baca [catatan kebutuhan dan desain](docs/design-system.md) untuk konflik sumber, token, komponen, dan batas asumsi. Konsep ImageGen disimpan di `docs/design/`. Foto login berasal dari aset project yang sudah tersedia di `../Prototype/assets/pkl-photo.png`, bukan dokumentasi kegiatan sekolah yang diverifikasi.

## Verifikasi

```powershell
php artisan test
php vendor/bin/pint --test
php artisan view:cache
npm run build
```

Feature test menggunakan environment testing dan session array, tanpa database pengguna. Browser diperiksa memakai Playwright dan Chrome lokal karena plugin Browser tidak tersedia. Rincian pemeriksaan dan batasnya terdapat pada [catatan verifikasi](docs/verification.md).
