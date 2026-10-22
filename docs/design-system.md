# Dasar kebutuhan dan sistem desain SIPKL

## Sumber yang diperiksa

`Referensi/Analyst/File Draw.io/File analyst V2.drawio` adalah XML dengan enam halaman: use case, ERD db_sipkl, wireframe Admin, Koordinator, Pembina, dan Siswa. Dokumen `use case scenario informasi diagram.docx` memuat UC01 sampai UC14. Seluruh teks diagram dan skenario dibaca melalui ekstraksi XML.

ZIP tampilan lama berisi `canvas.fig`, `thumbnail.png`, metadata, dan aset gambar. Thumbnail diperiksa, tetapi terlalu kecil untuk membaca seluruh label. ZIP bukan source aplikasi Laravel. Prototype HTML lama dan inventaris aset diperiksa. Tidak ditemukan composer.json atau package.json aplikasi sebelum scaffold baru dibuat.

Referensi resmi yang dibaca: [antrean Triage Linear](https://linear.app/docs/triage) untuk daftar, detail, dan tindakan tinjauan, serta [ringkasan pekerjaan siswa Google Classroom](https://support.google.com/edu/classroom/answer/9157286?hl=en) untuk status kegiatan dan umpan balik. Keduanya dipakai sebagai prinsip organisasi informasi. Logo dan identitas produk tidak disalin.

## Kebutuhan dari sumber

Empat peran login tetap Admin, Koordinator PKL, Pembina PKL, dan Siswa. Sekolah dan Industri adalah tipe profil Pembina. Siswa melihat miliknya sendiri, Pembina melihat relasi bimbingan, Koordinator mengelola data operasional, Admin mengelola akun dan konfigurasi.

Skenario menetapkan satu penempatan sepanjang program sekolah, absensi satu catatan per tanggal dengan waktu yang masuk akal, tanggal dalam periode, catatan wajib untuk permintaan revisi, dokumentasi jurnal berupa URL opsional, serta nilai akhir 0 sampai 100 dengan draf tersembunyi dari Siswa.

## Konflik dan keputusan UI

| Konflik | Implementasi pratinjau | Sebelum backend produksi |
| --- | --- | --- |
| Wireframe menampilkan nilai sementara kepada Siswa, skenario menyembunyikan draf | Tampilkan Nilai belum diterbitkan sampai status Terbit | Tentukan dan uji status penerbitan serta kepemilikan data |
| Wireframe memakai komponen dan bobot nilai, ERD hanya nilai akhir, catatan, tanggal | Satu nilai akhir, tanpa formula bobot | Rubrik memerlukan keputusan dan skema tambahan |
| Skenario memakai Draf/Terbit, ERD nilai belum memiliki status tersebut | Status hanya pada fixture/session contoh | Tambahkan dukungan skema jika alur diterapkan |
| Wireframe jurnal memakai pemilih file, ERD dan skenario memakai dokumentasi_url | Input URL http/https opsional | Sistem upload tidak dibuat tanpa keputusan |
| id_siswa di ERD hanya FK, skenario mewajibkan satu penempatan | Simulasi menolak penempatan kedua | Perlu unique constraint id_siswa dalam migration |
| Wireframe Pembina menawarkan penggantian tipe sendiri | Profil menampilkan Sekolah sebagai baca saja | Tipe harus berasal dari profil dan kewenangan server |
| Wireframe Admin memiliki tambah role | Matriks tetap empat peran | Tidak ada fitur tambah peran |
| Wireframe menawarkan pengingat/chat | Tidak dibuat tombol pesan palsu | Memerlukan kanal dan backend yang disepakati |
| Matriks lama mengizinkan Admin melihat data operasional, use case dan brief membedakan kewenangan | Menu Admin fokus pada akun, audit, konfigurasi dan laporan penggunaan | Izin baca lintas modul belum diaktifkan, perlu keputusan sebelum produksi |

## Keputusan visual dari brief

CSS `resources/css/app.css` adalah sumber token. Background #F7F8FA, surface #FFFFFF, sidebar dan teks #111827, hover sidebar #1F2937, teks sekunder #4B5563, metadata #6B7280, border permukaan #E5E7EB, border kontrol #9CA3AF, primary #2563EB, primary-hover #1D4ED8, primary-soft #EFF6FF. Merah hanya pada pesan error. Status selalu memiliki label teks.

Sans-serif tunggal dengan fallback lokal. Isi 14 sampai 16 px, metadata 12 px, heading desktop 32 px, mobile 26 px, judul bagian 18 sampai 19 px, angka ringkasan 30 px. Spacing dominan 8, 12, 16, 24, 32, 48 px. Radius kontrol 10 px, panel 14 px, dialog 16 px. Tombol dan target navigasi minimal 44 px. Ikon outline SVG dalam satu komponen, tanpa emoji.

Sidebar desktop 256 px, topbar 64 px, gutter desktop 32 px dan mobile 16 px. Pada lebar kurang dari 1024 px sidebar menjadi drawer. Daftar operasional berubah menjadi baris kartu pada mobile, sedangkan matriks izin tetap bisa digulir di dalam wadah. Jurnal desktop memakai daftar dan detail, mobile memakai detail dengan tautan kembali. Tidak ada bottom navigation, PWA, atau fitur offline.

Komponen Blade: icon, button, field, badge, empty, alert, stat, page-heading, dialog. Shell bersama mengelola sidebar, topbar, dan footer. Tabel dan form operasional berbagi template karena field serta perilakunya berulang. Modul jurnal dan penilaian memiliki template tersendiri.

## Konsep dan adaptasi

Konsep dibuat dengan tool ImageGen bawaan, bukan CLI. File hasil adalah `docs/design/login-concept.png`, `siswa-concept.png`, dan `jurnal-concept.png`. Brief generasi meminta screenshot aplikasi SIPKL penuh dengan sidebar charcoal, permukaan putih, aksen biru, login empat peran, tindakan harian siswa, serta antrean jurnal dengan detail dan umpan balik. Copy, navigasi, dan alur diambil dari brief pengguna. Konsep dipakai sebelum implementasi dan diperiksa kembali berdampingan dengan screenshot browser.

Beberapa hasil generasi menyimpang dari brief. Implementasi mempertahankan ketentuan eksplisit: menghilangkan lonceng tanpa data, gradien sidebar, badge hijau/oranye, dropdown penggantian tipe/peran pengguna di sidebar, serta syarat dua Pembina yang tidak ditemukan untuk penerbitan nilai. Angka ringkasan dihitung dari fixture yang benar-benar tersedia, sehingga tidak memakai angka dekoratif dari konsep. Foto yang dipasang memakai aset project yang sudah ada. Penjelasan pratinjau berada di area terpisah agar kontrol harian tetap mudah ditemukan.

## Asumsi terbatas

Data contoh menggunakan tanggal 8 Oktober 2026 dan periode 1 Agustus sampai 31 Oktober 2026. Nama, kontak, email, jurnal, dan nilai adalah fixture. Preview Pembina memilih contoh profil Sekolah, bukan memberi hak mengubah tipe. Status absensi yang diaktifkan hanya Hadir dan Izin karena keduanya ditemukan dalam wireframe. Status tambahan harus diputuskan sebelum backend.

Ringkasan kehadiran menyatakan jumlah Hadir dibanding catatan absensi yang tersedia, bukan persentase kehadiran kalender kerja. Tidak diasumsikan hari kerja, bobot nilai, jam operasional resmi, kuota tempat, notifikasi, atau sistem chat. Ringkasan periode dan tanggal dashboard tetap tanggal contoh, bukan klaim aktivitas waktu nyata.
