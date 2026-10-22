@extends('layouts.portal')

@section('title', 'Absensi')

@section('content')
<x-page-heading title="Absensi Siswa Bimbingan" description="Pantau kehadiran siswa yang berada di bawah bimbingan Anda."/>

<div class="stats">
    <x-stat label="Total absensi" :value="$totalCount.' catatan'"/>
    <x-stat label="Kehadiran" :value="$presentCount.' / '.$totalCount.' hari'"/>
</div>

<section class="panel table-panel">
    <h2>Riwayat absensi</h2>
    <form class="toolbar" method="get" action="{{ route('mentor.attendance.index') }}">
        <div class="field search-field">
            <label for="attendance-search">Cari siswa</label>
            <input type="search" id="attendance-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Nama atau NIS siswa">
        </div>
        <div class="field">
            <label for="status-filter">Status</label>
            <select id="status-filter" name="status">
                <option value="">Semua status</option>
                @foreach(['Hadir', 'Izin'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from-filter">Dari tanggal</label>
            <input type="date" id="from-filter" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to-filter">Sampai tanggal</label>
            <input type="date" id="to-filter" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('mentor.attendance.index') }}">Hapus filter</a>
    </form>

    @if($attendances->isNotEmpty())
        <div class="table-scroll">
            <table class="responsive-table">
                <caption class="sr-only">Riwayat absensi siswa bimbingan</caption>
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Siswa</th>
                        <th scope="col">Status</th>
                        <th scope="col">Jam masuk</th>
                        <th scope="col">Jam keluar</th>
                        <th scope="col">Lokasi absen</th>
                        <th scope="col">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                        <tr>
                            <td data-label="Tanggal">{{ $attendance->attendance_date->format('d-m-Y') }}</td>
                            <td data-label="Siswa">
                                <strong>{{ $attendance->student->name ?? 'Siswa' }}</strong>
                                <small class="muted">{{ $attendance->student->nis ?? '' }}</small>
                            </td>
                            <td data-label="Status">
                                <x-badge :status="$attendance->status"/>
                                @if($attendance->late_minutes)<small class="muted" style="display: block; color: var(--error);">Terlambat {{ $attendance->late_minutes }} mnt</small>@endif
                            </td>
                            <td data-label="Jam masuk">{{ $attendance->time_in ?: '—' }}</td>
                            <td data-label="Jam keluar">{{ $attendance->time_out ?: '—' }}</td>
                            <td data-label="Lokasi absen">
                                @if($attendance->check_in_distance !== null)
                                    <a href="https://www.google.com/maps?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" target="_blank" rel="noopener noreferrer">Masuk: {{ $attendance->check_in_distance }} m</a>
                                    @if($attendance->check_out_distance !== null)
                                        <br><a href="https://www.google.com/maps?q={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" target="_blank" rel="noopener noreferrer">Pulang: {{ $attendance->check_out_distance }} m</a>
                                    @endif
                                    <small class="muted" style="display: block;">dari titik tempat PKL</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Catatan">
                                {{ $attendance->note ?: '—' }}
                                @if($attendance->leave_photo_path)
                                    <a href="{{ asset('storage/'.$attendance->leave_photo_path) }}" target="_blank" rel="noopener noreferrer" style="display: block;">Lihat foto bukti</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <span>{{ $attendances->total() }} catatan</span>
            {{ $attendances->links() }}
        </div>
    @else
        <x-empty title="Belum ada absensi" description="Absensi siswa bimbingan akan muncul di sini."/>
    @endif
</section>
@endsection
