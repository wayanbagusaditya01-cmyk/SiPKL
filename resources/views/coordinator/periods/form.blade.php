@extends('layouts.portal')

@php($editing = $period !== null)

@section('title', $editing ? 'Ubah Periode PKL' : 'Tambah Periode PKL')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Periode PKL' : 'Tambah Periode PKL'" description="Tanggal selesai harus setelah tanggal mulai."/>
<section class="panel form-width"><form method="post" action="{{ $editing ? route('coordinator.periode.update', $period) : route('coordinator.periode.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <x-field name="name" label="Nama periode" :value="$period?->name ?? ''" required maxlength="255"/>
        <x-field name="academic_year" label="Tahun ajaran" :value="$period?->academic_year ?? ''" required maxlength="9" placeholder="2026/2027" pattern="\d{4}/\d{4}"/>
        <x-field name="start_date" label="Tanggal mulai" type="date" :value="$period?->start_date?->format('Y-m-d') ?? ''" required/>
        <x-field name="end_date" label="Tanggal selesai" type="date" :value="$period?->end_date?->format('Y-m-d') ?? ''" required/>
        <x-field name="status" label="Status periode" type="select" :options="['Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'Menunggu' => 'Menunggu']" :value="$period?->status ?? 'Menunggu'" required/>
    </div>
    <div class="actions"><x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan Periode' }}</x-button><x-button variant="secondary" :href="route('coordinator.periode.index')">Batal</x-button></div>
</form></section>
@endsection