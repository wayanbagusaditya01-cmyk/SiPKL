@extends('layouts.portal')

@section('title', 'Penempatan Saya')

@section('content')
<x-page-heading title="Penempatan Saya" description="Tempat dan periode PKL yang ditetapkan Koordinator."/>
@if(!$student->placement)
    <section class="panel"><x-empty title="Penempatan belum ditetapkan" description="Informasi penempatan akan muncul setelah Koordinator mengaturnya."/></section>
@else
    <section class="panel form-width">
        <div class="section-heading"><h2>{{ $student->placement->place->name }}</h2><x-badge :status="$student->placement->status"/></div>
        <dl><dt>Periode</dt><dd>{{ $student->placement->period?->name ?? 'Belum diatur' }}</dd><dt>Tahun ajaran</dt><dd>{{ $student->placement->period?->academic_year ?? 'Belum diatur' }}</dd><dt>Tanggal penempatan</dt><dd>{{ $student->placement->placed_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd><dt>Tempat</dt><dd>{{ $student->placement->place->name }}</dd></dl>
        <div class="actions"><x-button :href="route('student.portal.place')">Detail tempat PKL</x-button><x-button variant="secondary" :href="route('student.portal.mentors')">Pembina saya</x-button></div>
    </section>
@endif
@endsection