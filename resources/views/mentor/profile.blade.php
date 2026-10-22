@extends('layouts.portal')

@section('title', 'Profil Saya')

@section('content')
<x-page-heading title="Profil Saya" :description="'Data akun dan profil Anda sebagai '.$mentor->typeLabel().'.'"/>

<div class="content-grid">
    <section class="panel">
        <div class="section-heading">
            <h2>Data Pembina</h2>
            <x-badge :status="$mentor->type"/>
        </div>
        <dl>
            <dt>Nama</dt>
            <dd>{{ $user->name }}</dd>
            <dt>Email login</dt>
            <dd>{{ $user->email }}</dd>
            <dt>Tipe pembina</dt>
            <dd>{{ $mentor->typeLabel() }}</dd>
            @if($mentor->isIndustry())
                <dt>Jabatan</dt>
                <dd>{{ $mentor->position ?: 'Belum diisi' }}</dd>
                <dt>Tempat PKL binaan</dt>
                <dd>{{ $mentor->place?->name ?? 'Belum ditetapkan' }}</dd>
            @else
                <dt>NIP</dt>
                <dd>{{ $mentor->nip ?: 'Belum diisi' }}</dd>
            @endif
            <dt>Nomor telepon</dt>
            <dd>{{ $mentor->phone ?: ($user->phone ?: 'Belum diisi') }}</dd>
            <dt>Siswa dibimbing</dt>
            <dd>{{ $mentor->guidedPlacements()->count() }} siswa</dd>
        </dl>
        <small class="muted">Tipe, NIP, jabatan, dan tempat PKL dikelola oleh Koordinator PKL.</small>
    </section>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel">
            <div class="section-heading">
                <h2>Ubah Data Kontak</h2>
            </div>
            <form method="post" action="{{ route('mentor.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <x-field name="name" label="Nama lengkap" :value="$user->name" required maxlength="100"/>
                    <x-field name="email" label="Email login" type="email" :value="$user->email" required maxlength="255"/>
                    <x-field name="phone" label="Nomor telepon" :value="$mentor->phone ?? $user->phone ?? ''" maxlength="32" :required="$mentor->isIndustry()"/>
                </div>
                <div class="actions">
                    <x-button type="submit">Simpan Perubahan</x-button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Ubah Kata Sandi</h2>
            </div>
            <form method="post" action="{{ route('mentor.profile.password') }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <x-field name="current_password" label="Kata sandi saat ini" type="password" required/>
                    <x-field name="password" label="Kata sandi baru" type="password" required hint="Minimal 8 karakter."/>
                    <x-field name="password_confirmation" label="Konfirmasi kata sandi baru" type="password" required/>
                </div>
                <div class="actions">
                    <x-button type="submit" variant="secondary">Perbarui Kata Sandi</x-button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
