@extends('layouts.portal')

@php($editing = $mentor !== null)

@section('title', $editing ? 'Ubah Data Pembina' : 'Tambah Pembina')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Data Pembina' : 'Tambah Pembina'" description="Profil Pembina harus terhubung ke akun aktif yang dibuat Admin."/>
<section class="panel form-width"><form method="post" action="{{ $editing ? route('coordinator.pembina.update', $mentor) : route('coordinator.pembina.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <x-field name="user_id" label="Akun Pembina" type="select" :options="$accounts->mapWithKeys(fn ($account) => [$account->id => $account->name.' · '.$account->email.($account->role === 'pembina_industri' ? ' (Pembina Industri)' : ($account->role === 'pembina_sekolah' ? ' (Pembina Sekolah)' : ' (Pembina)'))])->all()" :value="$mentor?->user_id ?? ''" required hint="Hanya akun ber-role Pembina Sekolah atau Industri yang aktif dan belum terhubung."/>
        <x-field name="type" label="Tipe Pembina" type="select" :options="['Sekolah' => 'Pembina Sekolah', 'Industri' => 'Pembina Industri']" :value="$mentor?->type ?? ''" required/>
        <x-field name="nip" label="NIP" :value="$mentor?->nip ?? ''" maxlength="32" hint="Diisi untuk Pembina sekolah."/>
        <x-field name="position" label="Jabatan" :value="$mentor?->position ?? ''" maxlength="120" hint="Wajib untuk Pembina industri."/>
        <x-field name="phone" label="Nomor telepon" :value="$mentor?->phone ?? ''" maxlength="32" hint="Wajib untuk Pembina industri."/>
        <x-field name="place_id" label="Tempat PKL binaan" type="select" :options="$places->mapWithKeys(fn ($place) => [$place->id => $place->name])->all()" :value="$mentor?->place_id ?? ''" hint="Wajib untuk Pembina industri; kosongkan untuk Pembina sekolah."/>
    </div>
    <div class="actions"><x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan Pembina' }}</x-button><x-button variant="secondary" :href="route('coordinator.pembina.index')">Batal</x-button></div>
</form></section>
@endsection