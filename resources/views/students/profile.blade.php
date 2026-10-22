@extends('layouts.portal')

@section('title', 'Profil Saya')

@section('content')
<x-profile-header :name="$student->name" role="Siswa PKL" :subtitle="$user->email">
    <x-slot:meta>
        <div><dt>NIS</dt><dd>{{ $student->nis }}</dd></div>
        <div><dt>Kelas</dt><dd>{{ $student->class }}</dd></div>
        <div><dt>Tempat PKL</dt><dd>{{ $student->placement?->place?->name ?? 'Belum ditempatkan' }}</dd></div>
    </x-slot:meta>
</x-profile-header>

<div class="content-grid">
    <section class="panel">
        <div class="section-heading">
            <h2>Data Akademik</h2>
            <x-badge status="Aktif"/>
        </div>
        <dl>
            <dt>Nomor Induk Siswa (NIS)</dt>
            <dd>{{ $student->nis }}</dd>
            <dt>Nama Lengkap</dt>
            <dd>{{ $student->name }}</dd>
            <dt>Kelas</dt>
            <dd>{{ $student->class }}</dd>
            <dt>Jurusan</dt>
            <dd>{{ $student->major }}</dd>
            <dt>Email Akun Login</dt>
            <dd>{{ $user->email }}</dd>
            <dt>Tempat PKL</dt>
            <dd>{{ $student->placement?->place?->name ?? 'Belum ditempatkan' }}</dd>
            <dt>Periode PKL</dt>
            <dd>{{ $student->placement?->period?->name ?? 'Belum diatur' }}</dd>
            <dt>Pembina Sekolah</dt>
            <dd>{{ $student->placement?->schoolMentor?->user?->name ?? 'Belum ditetapkan' }}</dd>
            <dt>Pembina Industri</dt>
            <dd>{{ $student->placement?->industryMentor?->user?->name ?? 'Belum ditetapkan' }}</dd>
        </dl>
    </section>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel">
            <div class="section-heading">
                <h2>Ubah Data Kontak</h2>
            </div>
            <form method="post" action="{{ route('student.profile.update') }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <x-field name="name" label="Nama Siswa" :value="$student->name" required maxlength="255"/>
                    <x-field name="email" label="Email Kontak Siswa" type="email" :value="$student->email ?? ''" maxlength="255" hint="Email aktif untuk informasi dan komunikasi."/>
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
            <form method="post" action="{{ route('student.profile.password') }}">
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
