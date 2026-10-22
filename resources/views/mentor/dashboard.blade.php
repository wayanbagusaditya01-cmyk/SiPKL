@extends('layouts.portal')

@section('title', 'Ringkasan')

@section('content')
<x-page-heading title="Ringkasan Bimbingan" :description="$mentor->typeLabel().' · Pantau kegiatan siswa '.($mentor->isIndustry() ? 'magang di '.($mentor->place?->name ?? 'tempat PKL') : 'bimbingan sekolah').' secara langsung.'"/>

<div class="stats four">
    <x-stat label="Siswa bimbingan" :value="$studentCount.' siswa'"/>
    <x-stat label="Penempatan aktif" :value="$activePlacementCount.' siswa'"/>
    <x-stat label="Jurnal menunggu" :value="$pendingJournalCount.' entri'"/>
    <x-stat label="Sudah dinilai" :value="$gradedCount.' siswa'"/>
</div>

<div class="content-grid">
    <section class="panel">
        <div class="section-heading">
            <h2>Absensi terakhir</h2>
            <a href="{{ route('mentor.attendance.index') }}">Lihat semua</a>
        </div>
        @forelse($recentAttendances as $attendance)
            <div class="review-row">
                <span class="avatar">{{ mb_substr($attendance->student->name ?? '?', 0, 1) }}</span>
                <div>
                    <strong>{{ $attendance->student->name ?? 'Siswa' }}</strong>
                    <small>{{ $attendance->attendance_date->format('d-m-Y') }} · {{ $attendance->time_in ?: 'Tanpa jam masuk' }}{{ $attendance->time_out ? ' · '.$attendance->time_out : '' }}</small>
                </div>
                <x-badge :status="$attendance->status"/>
            </div>
        @empty
            <x-empty title="Belum ada absensi" description="Absensi siswa bimbingan akan muncul di sini."/>
        @endforelse
    </section>

    <section class="panel">
        <div class="section-heading">
            <div>
                <h2>Jurnal terbaru</h2>
                @if($pendingJournalCount > 0)
                    <p>{{ $pendingJournalCount }} jurnal menunggu tinjauan</p>
                @endif
            </div>
            <a href="{{ route('mentor.journals.index') }}">Lihat semua</a>
        </div>
        @forelse($recentJournals as $journal)
            <div class="review-row">
                <div>
                    <strong>{{ $journal->title }}</strong>
                    <small>{{ $journal->student->name ?? 'Siswa' }} · {{ $journal->activity_date->format('d-m-Y') }}</small>
                </div>
                <x-badge :status="$journal->status"/>
            </div>
        @empty
            <x-empty title="Belum ada jurnal" description="Jurnal siswa bimbingan akan muncul di sini."/>
        @endforelse
    </section>
</div>

<div class="actions" style="margin-top: 24px;">
    <x-button :href="route('mentor.students.index')"><x-icon name="users"/>Lihat Siswa Bimbingan</x-button>
    <x-button variant="secondary" :href="route('mentor.grades.index')"><x-icon name="grade"/>Kelola Penilaian</x-button>
</div>
@endsection
