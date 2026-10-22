@extends('layouts.portal')

@section('title', 'Nilai & Catatan')

@section('content')
<x-page-heading title="Nilai & Catatan" description="Hasil yang sudah diterbitkan oleh Pembina Sekolah dan Pembina Industri."/>
@if($grades->isEmpty())
    <section class="panel"><x-empty title="Nilai belum diterbitkan" description="Nilai dan catatan akan tampil setelah Pembina menerbitkannya."/></section>
@else
    @if($grades->count() > 1)
        <div class="stats">
            <x-stat label="Rata-rata nilai PKL" :value="number_format((float) $averageScore, 2)"/>
            <x-stat label="Penilai" :value="$grades->count().' pembina'"/>
        </div>
    @endif
    @foreach($grades as $grade)
        <section class="panel form-width"><div class="section-heading"><h2>{{ $grade->mentor?->typeLabel() ?? 'Penilaian PKL' }}</h2><x-badge status="Terbit"/></div><div class="grade-value">{{ number_format((float) $grade->score, 2) }}<span>/ 100</span></div><dl><dt>Catatan Pembina</dt><dd class="preserve-lines">{{ $grade->feedback ?: 'Belum ada catatan.' }}</dd><dt>Tanggal penilaian</dt><dd>{{ $grade->graded_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd><dt>Penilai</dt><dd>{{ $grade->mentor?->user?->name ?? 'Pembina PKL' }}</dd></dl></section>
    @endforeach
@endif
@endsection
