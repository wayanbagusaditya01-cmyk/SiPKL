@extends('layouts.portal')

@section('title', 'Ringkasan')

@section('content')
<x-page-heading title="Ringkasan Operasional PKL" description="Pantau progres penempatan siswa dan master data PKL secara langsung dari database."/>

<div class="stats four">
    <x-stat label="Siswa peserta" :value="$students->count().' siswa'"/>
    <x-stat label="Penempatan aktif" :value="$activePlacementCount.' siswa'"/>
    <x-stat label="Belum ditempatkan" :value="$unplacedStudents->count().' siswa'"/>
    <x-stat label="Tempat PKL" :value="$placeCount.' lokasi'"/>
</div>

<div class="content-grid">
    <section class="panel">
        <div class="section-heading">
            <div>
                <h2>Prioritas Operasional</h2>
                <p>Selesaikan penempatan agar siswa dapat memulai kegiatan.</p>
            </div>
            <x-button :href="route('coordinator.penempatan.create')"><x-icon name="plus"/>Tambah Penempatan</x-button>
        </div>

        @forelse($unplacedStudents->take(5) as $student)
            <div class="review-row">
                <span class="avatar">{{ mb_substr($student->name, 0, 1) }}</span>
                <div>
                    <strong>{{ $student->name }}</strong>
                    <small>{{ $student->nis }} · {{ $student->class }}</small>
                </div>
                <x-badge status="Belum ditempatkan"/>
                <a class="row-action" href="{{ route('coordinator.penempatan.create') }}">Tempatkan</a>
            </div>
        @empty
            <x-empty title="Semua siswa sudah ditempatkan" description="Tidak ada siswa terdaftar yang menunggu penempatan saat ini."/>
        @endforelse

        @if($unplacedStudents->count() > 5)
            <div style="margin-top: 16px; text-align: center;">
                <a href="{{ route('coordinator.siswa.index') }}" class="row-action">Lihat semua siswa ({{ $unplacedStudents->count() }} belum ditempatkan)</a>
            </div>
        @endif
    </section>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel">
            <div class="section-heading">
                <h2>Periode PKL Aktif</h2>
                <a href="{{ route('coordinator.periode.index') }}">Kelola periode</a>
            </div>
            @if($activePeriod)
                <dl>
                    <dt>Nama Periode</dt>
                    <dd>{{ $activePeriod->name }}</dd>
                    <dt>Tahun Ajaran</dt>
                    <dd>{{ $activePeriod->academic_year }}</dd>
                    <dt>Rentang Tanggal</dt>
                    <dd>{{ $activePeriod->start_date->format('d-m-Y') }} s/d {{ $activePeriod->end_date->format('d-m-Y') }}</dd>
                    <dt>Status</dt>
                    <dd><x-badge :status="$activePeriod->status"/></dd>
                </dl>
            @else
                <x-empty title="Belum ada periode aktif" description="Tambahkan periode PKL aktif agar siswa dapat mengisi absensi dan jurnal."/>
                <div class="actions" style="margin-top: 12px;">
                    <x-button :href="route('coordinator.periode.create')"><x-icon name="plus"/>Buat Periode Baru</x-button>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Penempatan Terbaru</h2>
                <a href="{{ route('coordinator.penempatan.index') }}">Lihat semua</a>
            </div>
            @forelse($placements as $placement)
                <div class="review-row">
                    <div>
                        <strong>{{ $placement->student?->name ?? 'Siswa dihapus' }}</strong>
                        <p>{{ $placement->place?->name ?? 'Tempat dihapus' }}</p>
                        <small>{{ $placement->placed_at?->format('d-m-Y') ?? 'Tanggal belum dicatat' }}</small>
                    </div>
                    <x-badge :status="$placement->status"/>
                </div>
            @empty
                <x-empty title="Belum ada penempatan" description="Data penempatan siswa akan muncul di sini."/>
            @endforelse
        </section>
    </div>
</div>
@endsection
