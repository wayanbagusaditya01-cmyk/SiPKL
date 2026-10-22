@extends('preview.base')
@section('title', 'Masuk')
@section('body')
<main id="main" class="login-layout">
    <section class="login-story"><a class="brand" href="{{ route('login') }}"><x-icon name="book"/>SIPKL</a><div><p class="story-title">Satu tempat untuk kegiatan PKL.</p><p>Penempatan, absensi, jurnal, dan bimbingan tersusun dalam satu aplikasi.</p></div><img class="login-photo" src="{{ asset('assets/pkl-photo.png') }}" alt="Siswa belajar menggunakan komputer dalam kegiatan praktik" width="640" height="440"><p class="photo-caption">Sistem Informasi Praktik Kerja Lapangan</p></section>
    <section class="login-panel"><button type="button" class="text-button about-link" data-dialog="about-dialog">Tentang pratinjau</button><div class="login-card"><div class="mobile-brand brand"><x-icon name="book"/>SIPKL</div><h1>Masuk ke SIPKL</h1><p>Pilih peran sesuai akun sekolahmu.</p>
    @if(session('success'))<x-alert>{{ session('success') }}</x-alert>@endif
    <fieldset class="role-group"><legend>Masuk sebagai</legend><div class="role-grid">
    @foreach($roles as $key => $item)<label class="role-choice"><input type="radio" name="role" value="{{ $key }}" data-name="{{ $item['name'] }}" @checked($loop->first)><x-icon :name="$key === 'admin' ? 'lock' : ($key === 'koordinator' ? 'calendar' : ($key === 'pembina' ? 'users' : 'user'))"/><span><strong>{{ $item['name'] }}</strong><small>{{ $item['description'] }}</small></span></label>@endforeach
    </div></fieldset>
    <div class="field"><label for="username">Username</label><input id="username" type="text" disabled placeholder="Autentikasi belum terhubung" autocomplete="off"></div>
    <div class="field"><label for="password">Kata sandi</label><div class="password-control"><input id="password" type="password" disabled placeholder="Belum tersedia" autocomplete="off"><button type="button" disabled>Tampilkan</button></div></div>
    <x-button class="full" disabled>Masuk</x-button><p class="login-note"><x-icon name="info"/>Autentikasi belum terhubung. Jangan gunakan kredensial nyata.</p>
    <a class="button secondary full" id="open-preview" href="{{ route('preview.page', ['siswa', 'dashboard']) }}" data-base="{{ url('/preview') }}">Buka pratinjau Siswa</a>
    <p class="help">Butuh bantuan? Hubungi Admin sekolah melalui kanal resmi.</p>
    <details class="login-states"><summary>Periksa contoh pesan login</summary><label for="login-state">Keadaan formulir</label><select id="login-state"><option value="initial">Awal</option><option value="role">Belum memilih peran</option><option value="empty">Kolom kosong</option><option value="invalid">Kredensial tidak valid</option><option value="mismatch">Peran tidak sesuai</option><option value="inactive">Akun belum aktif</option><option value="loading">Sedang memproses</option><option value="success">Berhasil masuk</option><option value="visible">Kata sandi terlihat</option><option value="hidden">Kata sandi tersembunyi</option></select><p id="login-state-message" role="status">Pilih peran untuk membuka pratinjau tanpa kredensial.</p><output id="sample-password" hidden aria-label="Kata sandi contoh"></output></details>
    </div></section>
</main>
@endsection
