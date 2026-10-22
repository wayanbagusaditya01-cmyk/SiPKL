@extends('layouts.portal')

@section('title', 'Ringkasan')

@section('content')
<x-hero :name="auth()->user()->name" :description="'Ringkasan Operasional PKL · '.($unplacedStudents->isEmpty() ? 'Semua siswa sudah ditempatkan' : $unplacedStudents->count().' siswa menunggu penempatan')">
    <x-slot:actions>
        <x-button :href="route('coordinator.penempatan.create')"><x-icon name="plus"/>Tambah penempatan</x-button>
        <x-button variant="secondary" :href="route('coordinator.siswa.index')"><x-icon name="users"/>Data siswa</x-button>
    </x-slot:actions>
    <x-slot:stats>
        <x-hero-stat icon="users" label="Siswa peserta" :value="$students->count()" :href="route('coordinator.siswa.index')"/>
        <x-hero-stat icon="check" label="Penempatan aktif" :value="$activePlacementCount" :href="route('coordinator.penempatan.index')"/>
        <x-hero-stat icon="info" label="Belum ditempatkan" :value="$unplacedStudents->count()" :href="route('coordinator.penempatan.create')"/>
        <x-hero-stat icon="place" label="Tempat PKL" :value="$placeCount" :href="route('coordinator.tempat.index')"/>
    </x-slot:stats>
</x-hero>

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
                <x-action :href="route('coordinator.penempatan.create')" variant="primary" icon="plus">Tempatkan</x-action>
            </div>
        @empty
            <x-empty title="Semua siswa sudah ditempatkan" description="Tidak ada siswa terdaftar yang menunggu penempatan saat ini."/>
        @endforelse

        @if($unplacedStudents->count() > 5)
            <div style="margin-top: 16px; text-align: center;">
                <x-action :href="route('coordinator.siswa.index')" icon="users">Lihat semua siswa ({{ $unplacedStudents->count() }} belum ditempatkan)</x-action>
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
