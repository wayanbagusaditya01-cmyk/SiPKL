@extends('layouts.portal')

@php($editing = $placement !== null)

@section('title', $editing ? 'Ubah Penempatan' : 'Tambah Penempatan')

@section('content')
<x-page-heading :title="$editing ? 'Ubah Penempatan' : 'Tambah Penempatan'" description="Pilih periode dan pembina yang sesuai dengan tempat PKL siswa."/>
<section class="panel form-width"><form method="post" action="{{ $editing ? route('coordinator.penempatan.update', $placement) : route('coordinator.penempatan.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="form-grid">
        <x-field name="student_id" label="Siswa" type="select" :options="$students->mapWithKeys(fn ($student) => [$student->id => $student->nis.' · '.$student->name.' · '.$student->class])->all()" :value="$placement?->student_id ?? ''" required/>
        <x-field name="place_id" label="Tempat PKL" type="select" :options="$places->mapWithKeys(fn ($place) => [$place->id => $place->name])->all()" :value="$placement?->place_id ?? ''" required/>
        <x-field name="period_id" label="Periode PKL" type="select" :options="$periods->mapWithKeys(fn ($period) => [$period->id => $period->name.' · '.$period->academic_year])->all()" :value="$placement?->period_id ?? ''" required/>
        <x-field name="school_mentor_id" label="Pembina sekolah" type="select" :options="$schoolMentors->mapWithKeys(fn ($mentor) => [$mentor->id => $mentor->user->name])->all()" :value="$placement?->school_mentor_id ?? ''" required/>
        <x-field name="industry_mentor_id" label="Pembina industri untuk tempat ini" type="select" :options="$industryMentors->mapWithKeys(fn ($mentor) => [$mentor->id => $mentor->user->name.' · '.($mentor->place?->name ?? 'Tempat belum dipilih')])->all()" :value="$placement?->industry_mentor_id ?? ''" required/>
        <x-field name="placed_at" label="Tanggal penempatan" type="date" :value="$placement?->placed_at?->format('Y-m-d') ?? ''"/>
        <x-field name="status" label="Status" type="select" :options="['Aktif' => 'Aktif', 'Selesai' => 'Selesai']" :value="$placement?->status ?? 'Aktif'" required/>
    </div>
    <div class="actions"><x-button type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan Penempatan' }}</x-button><x-button variant="secondary" :href="route('coordinator.penempatan.index')">Batal</x-button></div>
</form></section>
@endsection