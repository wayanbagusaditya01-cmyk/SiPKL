@extends('layouts.portal')

@section('title', 'Ringkasan PKL Saya')

@section('content')
@php($attendanceSummary = $presentCount.' / '.$attendanceCount.' hari')
<x-page-heading title="Ringkasan PKL Saya" description="Kegiatan dan progres yang tersimpan pada akunmu."/>

<div class="stats">
    <x-stat label="Kehadiran tercatat" :value="$attendanceSummary"/>
    <x-stat label="Jurnal tersimpan" :value="$journalCount.' entri'"/>
    <x-stat label="Jurnal menunggu" :value="$waitingJournalsCount.' entri'"/>
</div>

@if($student->placement)
    <section class="panel placement-summary">
        <div class="section-heading"><div><h2>Penempatan saya</h2><p>{{ $student->placement->period?->name ?? 'Periode belum diatur' }}</p></div><x-badge :status="$student->placement->status"/></div>
        <h3>{{ $student->placement->place->name }}</h3>
        <dl><dt>Alamat</dt><dd>{{ $student->placement->place->address ?: 'Belum dicatat' }}</dd><dt>Tanggal penempatan</dt><dd>{{ $student->placement->placed_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd><dt>Pembina sekolah</dt><dd>{{ $student->placement->schoolMentor?->user?->name ?? 'Belum ditetapkan' }}</dd><dt>Pembina industri</dt><dd>{{ $student->placement->industryMentor?->user?->name ?? 'Belum ditetapkan' }}</dd><dt>Periode</dt><dd>@if($student->placement->period){{ $student->placement->period->start_date->format('d-m-Y') }} sampai {{ $student->placement->period->end_date->format('d-m-Y') }}@else Belum diatur @endif</dd></dl>
        <div class="actions"><x-button :href="route('student.portal.placement')">Lihat penempatan</x-button><x-button variant="secondary" :href="route('student.portal.mentors')">Lihat Pembina</x-button></div>
    </section>
@else
    <section class="panel"><x-empty title="Penempatan belum ditetapkan" description="Koordinator PKL belum menetapkan tempat untuk profilmu."/></section>
@endif

<div class="content-grid">
    <section class="panel">
        <div class="section-heading"><h2>Absensi terakhir</h2><a href="{{ route('student.attendance.index') }}">Lihat absensi</a></div>
        @forelse($attendances as $attendance)
            <div class="review-row"><div><strong>{{ $attendance->attendance_date->format('d-m-Y') }}</strong><small>{{ $attendance->time_in ?: 'Tanpa jam masuk' }}{{ $attendance->time_out ? ' · '.$attendance->time_out : '' }}</small></div><x-badge :status="$attendance->status"/></div>
        @empty
            <x-empty title="Belum ada absensi" description="Absensi yang kamu simpan akan muncul di sini."/>
        @endforelse
    </section>
    <section class="panel">
        <div class="section-heading"><h2>Jurnal terbaru</h2><a href="{{ route('student.journals.index') }}">Lihat jurnal</a></div>
        @forelse($recentJournals as $journal)
            <div class="review-row"><div><strong>{{ $journal->title }}</strong><small>{{ $journal->activity_date->format('d-m-Y') }}</small></div><x-badge :status="$journal->status"/></div>
        @empty
            <x-empty title="Belum ada jurnal" description="Draf dan jurnal yang kamu kirim akan muncul di sini."/>
        @endforelse
    </section>
</div>

@if($publishedGrade)
    <x-alert type="success">Nilai PKL sudah diterbitkan. <a href="{{ route('student.portal.grade') }}">Lihat nilai dan catatan</a>.</x-alert>
@else
    <x-alert>Nilai belum diterbitkan Pembina.</x-alert>
@endif
@endsection