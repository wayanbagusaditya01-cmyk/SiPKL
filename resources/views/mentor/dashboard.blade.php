@extends('layouts.portal')

@section('title', 'Ringkasan')

@section('content')
<x-hero :name="auth()->user()->name" :description="$mentor->typeLabel().($mentor->isIndustry() && $mentor->place ? ' · '.$mentor->place->name : '').($pendingJournalCount ? ' · '.$pendingJournalCount.' jurnal menunggu tinjauanmu' : ' · Semua jurnal sudah ditinjau')">
    <x-slot:actions>
        <x-button :href="route('mentor.journals.index')"><x-icon name="book"/>Tinjau jurnal</x-button>
        @if($mentor->isIndustry() && $mentor->place_id)
            <x-button variant="secondary" :href="route('mentor.schedule.edit')"><x-icon name="calendar"/>Jadwal & Lokasi</x-button>
        @else
            <x-button variant="secondary" :href="route('mentor.grades.index')"><x-icon name="grade"/>Penilaian</x-button>
        @endif
    </x-slot:actions>
    <x-slot:stats>
        <x-hero-stat icon="users" label="Siswa bimbingan" :value="$studentCount" :href="route('mentor.students.index')"/>
        <x-hero-stat icon="place" label="Penempatan aktif" :value="$activePlacementCount" :href="route('mentor.placements.index')"/>
        <x-hero-stat icon="book" label="Jurnal menunggu" :value="$pendingJournalCount" :href="route('mentor.journals.index')"/>
        <x-hero-stat icon="grade" label="Sudah dinilai" :value="$gradedCount" :href="route('mentor.grades.index')"/>
    </x-slot:stats>
</x-hero>

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
