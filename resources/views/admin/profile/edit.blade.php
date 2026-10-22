@extends('layouts.portal')

@section('title', 'Profil Admin')

@section('content')
<x-page-heading title="Profil Admin" description="Identitas akun administrator dan pengaturan keamanan kata sandi."/>

@if(session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif
@if($errors->any())
    <x-alert type="error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

<div class="content-grid" style="align-items: flex-start;">
    <section class="panel">
        <div class="section-heading">
            <h2>Data Pribadi Admin</h2>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="field" style="margin-bottom: 12px;">
                <label for="name">Nama lengkap</label>
                <input type="text" id="name" name="name" required maxlength="255" value="{{ old('name', $user->name) }}">
            </div>

            <div class="field" style="margin-bottom: 12px;">
                <label for="email">Alamat email</label>
                <input type="email" id="email" name="email" required maxlength="255" value="{{ old('email', $user->email) }}">
            </div>

            <div class="field" style="margin-bottom: 16px;">
                <label for="phone">Nomor telepon</label>
                <input type="text" id="phone" name="phone" maxlength="32" placeholder="Contoh: 081234567890" value="{{ old('phone', $user->phone) }}">
            </div>

            <dl style="margin-bottom: 20px;">
                <dt>Peran Sistem</dt>
                <dd>Administrator</dd>
                <dt>Status Akun</dt>
                <dd><x-badge status="Aktif"/></dd>
                <dt>Terdaftar Sejak</dt>
                <dd>{{ $user->created_at?->format('d F Y') ?? '-' }}</dd>
            </dl>

            <div class="actions">
                <x-button type="submit">Simpan Perubahan Profil</x-button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading">
            <h2>Ubah Kata Sandi</h2>
        </div>
        <p style="font-size: 13px; color: var(--muted, #64748b); margin-top: 0;">
            Gunakan kata sandi yang kuat dengan minimal 12 karakter.
        </p>

        <form method="POST" action="{{ route('admin.profile.password') }}">
            @csrf
            @method('PUT')

            <div class="field" style="margin-bottom: 12px;">
                <label for="current_password">Kata sandi saat ini</label>
                <input type="password" id="current_password" name="current_password" required placeholder="Masukkan kata sandi saat ini">
            </div>

            <div class="field" style="margin-bottom: 12px;">
                <label for="password">Kata sandi baru (min. 12 karakter)</label>
                <input type="password" id="password" name="password" required minlength="12" placeholder="Masukkan kata sandi baru">
            </div>

            <div class="field" style="margin-bottom: 20px;">
                <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="12" placeholder="Ulangi kata sandi baru">
            </div>

            <div class="actions">
                <x-button type="submit">Perbarui Kata Sandi</x-button>
            </div>
        </form>
    </section>
</div>
@endsection
