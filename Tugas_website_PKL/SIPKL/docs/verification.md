# Verifikasi SIPKL UI

Pemeriksaan dilakukan pada 8 Oktober 2026 di Windows, PHP 8.5.9, Laravel 13.35.0, Node 24.19.0, Vite 8.3.3. URL pemeriksaan adalah http://127.0.0.1:8000. Plugin Browser tidak tersedia pada sesi ini. Pengujian memakai Playwright yang sudah dibundel dan Chrome lokal, tanpa memasang browser dependency baru.

## Hasil terakhir

| Pemeriksaan | Hasil |
| --- | --- |
| php artisan test | 14 test lulus, 163 assertion |
| php vendor/bin/pint --test | Lulus |
| php artisan view:cache | Template berhasil dikompilasi |
| npm run build | Lulus, CSS dan JavaScript dihasilkan Vite |
| composer validate --no-check-publish | Manifest dan lockfile valid |
| npm audit --json | 0 kerentanan dilaporkan pada dependency terpasang |
| Identitas halaman dan konten | HTTP 200, judul SIPKL, heading terlihat pada seluruh URL yang diuji |
| Horizontal overflow | Tidak ditemukan pada 240 kombinasi URL dan viewport |
| Aset gambar | Semua gambar yang dipasang selesai dimuat dan memiliki naturalWidth |
| Console dan runtime | Tidak ditemukan pageerror, console error, atau respons 400 ke atas selama uji browser |
| Screenshot visual | Login, empat dashboard, desktop jurnal, mobile login, siswa, dan jurnal diperiksa |
| Alur interaksi | Lulus pada alur yang dirinci berikut |

240 kombinasi berasal dari 40 URL pada masing-masing lebar 360, 390, 768, 1024, 1366, dan 1440 px. URL mencakup login, 28 halaman menu, enam form buat, keadaan tanpa penempatan, jurnal revisi, detail akun, detail siswa, dan alamat tempat yang panjang.

## Alur browser yang benar-benar dijalankan

Pilihan login mengubah link pratinjau sesuai peran. Tombol panah keyboard memindahkan radio ke peran berikutnya. Dialog Tentang Pratinjau dapat ditutup dengan Escape dan mengembalikan fokus. Drawer mobile memfokuskan kontrol di dalamnya, melingkarkan fokus dari kontrol terakhir ke pertama, menutup dengan Escape, mengembalikan fokus ke pemicu, serta menutup setelah navigasi ke Absensi.

Status Izin menyembunyikan jam dan mewajibkan catatan. Simpan Absensi menambah catatan contoh. Pengiriman ulang tanggal sama ditolak. Minta Revisi tanpa catatan menampilkan validasi browser. Revisi dengan catatan muncul di sisi Siswa dan dapat dikirim ulang. Sisi Pembina menampilkan uraian yang sudah diperbaiki.

Simpan Draf Nilai tidak memperlihatkan angkanya pada Siswa. Terbitkan Nilai membuka konfirmasi, Escape membatalkan dan mengembalikan fokus, dan konfirmasi membuat hasil tampil kepada Siswa. Pemilihan tempat tanpa pembina industri menghasilkan pemilih kosong dengan penjelasan. Tempat yang memiliki Pembina menampilkan pilihan yang sesuai. Ringkasan penempatan menampilkan siswa yang dipilih sebelum konfirmasi dan hasilnya muncul di daftar.

Pencarian tanpa hasil menampilkan empty state. Reset mengembalikan data contoh. Test Laravel juga memeriksa tanggal di luar periode, waktu keluar sebelum masuk, URL dokumentasi berbahaya, perubahan jurnal yang sudah disetujui, nilai di luar 0 sampai 100, penempatan ganda, akses contoh di luar bimbingan, aktivasi tanpa profil, penautan profil, filter tabel, dan penyembunyian jurnal draf dari Pembina.

## Temuan yang sudah diperbaiki

Field tersembunyi bernama id menutupi properti form.id di browser. Validasi revisi sekarang memakai getAttribute sehingga catatan wajib diperiksa sebelum pengiriman. Test browser semula menggunakan heading panel desktop yang disembunyikan pada mobile. Hierarki login diperbaiki menjadi satu h1 yang terlihat pada semua ukuran. Penambahan blok PHP filter menemukan konflik direktif inline @php dengan blok @php berikutnya. Template diperbaiki dan feature test serta browser diulang sampai lulus.

Dependency concurrently dan shell-quote dari scaffold tidak diperlukan oleh UI ini dan sempat dilaporkan oleh audit npm. Paket tersebut dihapus bersama plugin Tailwind yang tidak dipakai. Dependency aset sekarang hanya Vite dan laravel-vite-plugin. Audit diulang dan melaporkan nol temuan.

## Bukti screenshot

Screenshot final dan script QA disimpan di direktori visualizations sesi, di luar source aplikasi. Konsep yang menjadi bahan desain disimpan di docs/design. Keduanya dibaca dengan view_image untuk pemeriksaan visual. Perbedaan yang disengaja terhadap hasil ImageGen dicatat di design-system.md.

- [Login desktop](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/login-desktop.png>)
- [Dashboard Siswa desktop](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/siswa-desktop.png>)
- [Dashboard Admin](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/admin-desktop.png>)
- [Dashboard Koordinator](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/koordinator-desktop.png>)
- [Tinjauan jurnal Pembina](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/pembina-desktop.png>)
- [Login mobile](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/login-mobile.png>)
- [Dashboard Siswa mobile](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/siswa-mobile.png>)
- [Detail jurnal mobile](<C:/Users/BAGUS/.codex/visualizations/2026/10/08/01a118f4-7c70-7cd0-a26a-8e58d86c56e3/jurnal-mobile.png>)

## Batas pemeriksaan

Belum diuji di Safari, Firefox, Android atau iOS fisik, keyboard layar perangkat nyata, pembaca layar, serta layanan peta eksternal. Resize viewport bukan pengujian keyboard virtual. Login, penyimpanan database, integrasi akun nyata, otorisasi produksi, upload, dan ekspor belum tersedia sehingga tidak diklaim teruji. Data draf dan perubahan yang diuji berada pada session contoh, bukan database sekolah.

Plugin Browser dapat dipasang untuk pemeriksaan berikutnya yang terintegrasi langsung dengan panel browser Codex.
