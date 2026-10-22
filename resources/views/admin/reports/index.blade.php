@extends('layouts.portal')

@section('title', 'Laporan Penggunaan Sistem')

@section('content')
<x-page-heading title="Laporan Penggunaan Sistem" description="Ringkasan penggunaan, status akun pengguna, dan aktivitas operasional dari database produksi SIPKL.">
    <x-button :href="route('admin.reports.export-csv')">
        <x-icon name="file"/>Unduh Laporan CSV
    </x-button>
</x-page-heading>

<div class="stats four">
    <x-stat label="Akun aktif" :value="$stats['activeAccounts'].' dari '.$stats['totalAccounts']"/>
    <x-stat label="Aktivitas tercatat" :value="$stats['totalLogs'].' log'"/>
    <x-stat label="Penempatan aktif" :value="$stats['activePlacements'].' siswa'"/>
    <x-stat label="Jurnal disetujui" :value="$stats['approvedJournals'].' entri'"/>
</div>

<section class="panel">
    <div class="section-heading">
        <div>
            <h2>Status Akun per Peran</h2>
            <p>Distribusi akun dan status keaktifan hak akses pengguna.</p>
        </div>
        <a href="{{ route('admin.accounts.index') }}">Kelola akun</a>
    </div>

    <div class="table-scroll">
        <table class="responsive-table">
            <caption class="sr-only">Laporan akun</caption>
            <thead>
                <tr>
                    <th scope="col">Peran</th>
                    <th scope="col">Total Akun</th>
                    <th scope="col">Akun Aktif</th>
                    <th scope="col">Akun Nonaktif</th>
                    <th scope="col">Persentase Aktif</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accountReport as $item)
                    @php
                        $percentage = $item['total'] > 0 ? round(($item['active'] / $item['total']) * 100) : 0;
                    @endphp
                    <tr>
                        <td data-label="Peran"><strong>{{ $item['name'] }}</strong></td>
                        <td data-label="Total Akun">{{ $item['total'] }}</td>
                        <td data-label="Akun Aktif"><x-badge status="Aktif"/> {{ $item['active'] }}</td>
                        <td data-label="Akun Nonaktif">
                            @if($item['inactive'] > 0)
                                <x-badge status="Nonaktif"/> {{ $item['inactive'] }}
                            @else
                                <span style="color: var(--muted, #64748b); font-size: 13px;">0</span>
                            @endif
                        </td>
                        <td data-label="Persentase Aktif">
                            <strong>{{ $percentage }}%</strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div class="content-grid" style="margin-top: 24px;">
    <section class="panel">
        <div class="section-heading">
            <h2>Ringkasan Operasional PKL</h2>
        </div>
        <dl>
            <dt>Total Siswa Terdaftar</dt>
            <dd>{{ $stats['totalStudents'] }} siswa</dd>
            <dt>Total Pembina</dt>
            <dd>{{ $stats['totalMentors'] }} pembina</dd>
            <dt>Total Tempat PKL</dt>
            <dd>{{ $stats['totalPlaces'] }} lokasi</dd>
            <dt>Penempatan Terdaftar</dt>
            <dd>{{ $stats['totalPlacements'] }} siswa</dd>
        </dl>
    </section>

    <section class="panel">
        <div class="section-heading">
            <h2>Aktivitas Kehadiran & Jurnal</h2>
        </div>
        <dl>
            <dt>Total Riwayat Absensi</dt>
            <dd>{{ $stats['totalAttendances'] }} catatan</dd>
            <dt>Kehadiran Hadir</dt>
            <dd>{{ $stats['hadirAttendances'] }} hari</dd>
            <dt>Kehadiran Izin</dt>
            <dd>{{ $stats['izinAttendances'] }} hari</dd>
            <dt>Total Jurnal Masuk</dt>
            <dd>{{ $stats['totalJournals'] }} jurnal ({{ $stats['pendingJournals'] }} menunggu verifikasi)</dd>
        </dl>
    </section>
</div>

<div class="actions" style="margin-top: 24px;">
    <x-button :href="route('admin.reports.export-csv')">
        <x-icon name="file"/>Unduh Laporan Lengkap (CSV)
    </x-button>
</div>
@endsection
