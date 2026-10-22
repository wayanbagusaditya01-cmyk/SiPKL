@extends('layouts.portal')

@php($editing = $place !== null)

@section('title', $editing ? 'Ubah Tempat PKL' : 'Tambah Tempat PKL')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Tempat PKL' : 'Tambah Tempat PKL'" description="Tempat disimpan sebagai master dan dapat dipakai oleh beberapa siswa."/>
<section class="panel form-width"><form method="post" action="{{ $editing ? route('coordinator.tempat.update', $place) : route('coordinator.tempat.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <x-field name="name" label="Nama tempat" :value="$place?->name ?? ''" required maxlength="255"/>
        <x-field name="email" label="Email kontak" type="email" :value="$place?->email ?? ''" maxlength="255"/>
        <x-field name="contact" label="Nomor kontak" :value="$place?->contact ?? ''" maxlength="100"/>
        <x-field name="address" label="Alamat lengkap" type="textarea" :value="$place?->address ?? ''" maxlength="3000"/>
        <x-field name="latitude" label="Latitude" type="number" :value="$place?->latitude ?? ''" step="any" min="-90" max="90"/>
        <x-field name="longitude" label="Longitude" type="number" :value="$place?->longitude ?? ''" step="any" min="-180" max="180"/>
    </div>
    <div class="actions"><x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan Tempat' }}</x-button><x-button variant="secondary" :href="route('coordinator.tempat.index')">Batal</x-button></div>
</form></section>
@endsection