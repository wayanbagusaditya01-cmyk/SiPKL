@extends('layouts.portal')

@php($editing = $place !== null)

@section('title', $editing ? 'Ubah Tempat PKL' : 'Tambah Tempat PKL')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Tempat PKL' : 'Tambah Tempat PKL'" description="Tempat disimpan sebagai master dan dapat dipakai oleh beberapa siswa."/>
<div class="form-layout"><section class="panel"><form method="post" action="{{ $editing ? route('coordinator.tempat.update', $place) : route('coordinator.tempat.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <x-field name="name" label="Nama tempat" :value="$place?->name ?? ''" required maxlength="255"/>
        <x-field name="email" label="Email kontak" type="email" :value="$place?->email ?? ''" maxlength="255"/>
        <x-field name="contact" label="Nomor kontak" :value="$place?->contact ?? ''" maxlength="100"/>
        <x-field name="address" label="Alamat lengkap" type="textarea" :value="$place?->address ?? ''" maxlength="3000"/>
        <x-field name="latitude" label="Latitude" type="number" :value="$place?->latitude ?? ''" step="any" min="-90" max="90"/>
        <x-field name="longitude" label="Longitude" type="number" :value="$place?->longitude ?? ''" step="any" min="-180" max="180"/>
        <x-field name="attendance_radius" label="Radius absensi (meter)" type="number" :value="$place?->attendance_radius ?? 150" min="25" max="2000" step="1" required hint="Siswa hanya bisa absen masuk/pulang dalam jarak ini dari titik koordinat tempat PKL."/>
    </div>
    <div class="actions"><x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan Tempat' }}</x-button><x-button variant="secondary" :href="route('coordinator.tempat.index')">Batal</x-button></div>
</form></section>
<x-form-guide :items="[
    'Nama tempat harus unik dan dapat dipakai untuk beberapa siswa.',
    'Isi latitude dan longitude agar siswa dapat absen berbasis lokasi.',
    'Radius absensi antara 25 sampai 2.000 meter dari titik tempat.',
    'Pembina Industri dapat memperbarui titik lokasi dan jadwal kerja dari portalnya.',
]"/></div>
@endsection