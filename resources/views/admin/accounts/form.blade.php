@extends('layouts.portal')

@php($editing = $account !== null)

@section('title', $editing ? 'Ubah Akun' : 'Buat Akun')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Akun' : 'Buat Akun'" description="Akun menggunakan email dan peran yang ditetapkan Admin."/>

<div class="form-layout"><section class="panel">
    <form method="post" action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="form-grid">
            <x-field name="name" label="Nama lengkap" :value="$account?->name ?? ''" required maxlength="255" autocomplete="name"/>
            <x-field name="email" label="Email" type="email" :value="$account?->email ?? ''" required maxlength="255" autocomplete="email"/>
            <x-field name="phone" label="Nomor telepon" type="text" :value="$account?->phone ?? ''" maxlength="32" autocomplete="tel"/>
            <x-field name="role" label="Peran akun" type="select" :options="collect($roles)->mapWithKeys(fn ($role, $key) => [$key => $role['name']])->all()" :value="$account?->role ?? ''" required/>
            <x-field name="status" label="Status akun" type="select" :options="['1' => 'Aktif', '0' => 'Nonaktif']" :value="$editing ? ($account->status ? '1' : '0') : '1'" required/>
            <x-field name="password" label="Kata sandi" type="password" :required="!$editing" minlength="12" autocomplete="new-password" :hint="$editing ? 'Kosongkan jika tidak ingin mengganti kata sandi. Minimal 12 karakter.' : 'Minimal 12 karakter.'"/>
            <x-field name="password_confirmation" label="Ulangi kata sandi" type="password" :required="!$editing" minlength="12" autocomplete="new-password"/>
        </div>
        <div class="actions">
            <x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat akun' }}</x-button>
            <x-button variant="secondary" :href="route('admin.accounts.index')">Batal</x-button>
        </div>
    </form>
</section>
<x-form-guide :items="[
    'Email dipakai untuk masuk dan harus unik.',
    'Kata sandi minimal 12 karakter.',
    'Akun siswa otomatis dibuatkan profil siswa.',
    'Profil pembina dihubungkan Koordinator PKL melalui menu Data Pembina.',
    'Akun nonaktif tidak dapat masuk ke SIPKL.',
]"/></div>
@endsection