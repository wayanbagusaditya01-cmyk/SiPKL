@extends('layouts.portal')

@php($editing = $student !== null)

@section('title', $editing ? 'Ubah Data Siswa' : 'Tambah Siswa')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Data Siswa' : 'Tambah Siswa'" description="Profil siswa dapat disimpan sebelum akun login dibuat. Penempatan dikelola melalui menu Penempatan."/>

<section class="panel form-width">
    <form method="post" action="{{ $editing ? route('coordinator.siswa.update', $student) : route('coordinator.siswa.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="form-grid">
            <x-field name="nis" label="NIS" :value="$student?->nis ?? ''" required maxlength="32"/>
            <x-field name="name" label="Nama siswa" :value="$student?->name ?? ''" required maxlength="255" autocomplete="name"/>
            <x-field name="class" label="Kelas" :value="$student?->class ?? ''" required maxlength="100"/>
            <x-field name="major" label="Jurusan" :value="$student?->major ?? ''" required maxlength="150"/>
            <x-field name="email" label="Email kontak" type="email" :value="$student?->email ?? ''" maxlength="255" autocomplete="email"/>
            <x-field name="user_id" label="Akun login siswa" type="select" :options="$accounts->mapWithKeys(fn ($account) => [$account->id => $account->name.' · '.$account->email])->all()" :value="$student?->user_id ?? ''" hint="Opsional. Hanya akun siswa aktif yang belum terhubung dapat dipilih."/>
        </div>
        <div class="actions">
            <x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan siswa' }}</x-button>
            <x-button variant="secondary" :href="route('coordinator.siswa.index')">Batal</x-button>
        </div>
    </form>
</section>
@endsection