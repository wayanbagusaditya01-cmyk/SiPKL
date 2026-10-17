@extends('layouts.portal')

@section('title', 'Nilai & Catatan')

@section('content')
<x-page-heading title="Nilai & Catatan" description="Hasil yang sudah diterbitkan oleh Pembina PKL."/>
@if(!$grade)
    <section class="panel"><x-empty title="Nilai belum diterbitkan" description="Nilai dan catatan akan tampil setelah Pembina menerbitkannya."/></section>
@else
    <section class="panel form-width"><div class="section-heading"><h2>Hasil penilaian PKL</h2><x-badge status="Terbit"/></div><div class="grade-value">{{ number_format((float) $grade->score, 2) }}<span>/ 100</span></div><dl><dt>Catatan Pembina</dt><dd class="preserve-lines">{{ $grade->feedback ?: 'Belum ada catatan.' }}</dd><dt>Tanggal penilaian</dt><dd>{{ $grade->graded_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd><dt>Penilai</dt><dd>{{ $grade->mentor?->user?->name ?? 'Pembina PKL' }}</dd></dl></section>
@endif
@endsection