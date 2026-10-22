<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'SIPKL') | Pratinjau SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/book-open.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip" href="#main">Lewati navigasi</a>
    @yield('body')
    <x-dialog/>
    <dialog id="about-dialog" aria-labelledby="about-title"><div class="dialog-heading"><h2 id="about-title">Tentang pratinjau</h2><button type="button" class="icon-button" data-close-dialog aria-label="Tutup dialog"><x-icon name="close"/></button></div><p>Semua identitas dan aktivitas merupakan data contoh. Login, hak akses produksi, ekspor, dan penyimpanan database belum terhubung.</p><p>Form yang aktif hanya mengubah data contoh dalam sesi browser ini. Jangan masukkan data pribadi atau kredensial nyata.</p><form action="{{ route('preview.reset') }}" method="post" data-confirm="Kembalikan semua data contoh ke kondisi awal?">@csrf<x-button type="submit" variant="secondary">Reset data contoh</x-button></form></dialog>
</body>
</html>
