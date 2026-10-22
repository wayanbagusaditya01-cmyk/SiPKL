@extends('layouts.portal')

@section('title', 'Ringkasan Sistem')

@section('content')
<x-page-heading title="Ringkasan Sistem" description="Status akun pengguna, distribusi peran, dan aktivitas sistem yang tersimpan di database.">
    <x-button :href="route('admin.accounts.create')"><x-icon name="plus"/>Buat Akun</x-button>
</x-page-heading>

<div class="stats four">
    <x-stat label="Total akun" :value="$stats['totalAccounts'].' akun'"/>
    <x-stat label="Akun aktif" :value="$stats['activeAccounts'].' aktif'"/>
    <x-stat label="Akun nonaktif" :value="$stats['inactiveAccounts'].' nonaktif'"/>
    <x-stat label="Penempatan PKL" :value="$stats['totalPlacements'].' siswa'"/>
</div>

<div class="content-grid">
    <section class="panel">
        <div class="section-heading">
            <div>
                <h2>Akun Perlu Ditindaklanjuti</h2>
                <p>Periksa kelengkapan profil dan aktivasi akun pengguna.</p>
            </div>
            <x-button variant="secondary" :href="route('admin.accounts.index')">Semua Akun</x-button>
        </div>

        @php
            $hasFollowUps = $unlinkedStudents->isNotEmpty() || $unlinkedMentors->isNotEmpty() || $inactiveUsers->isNotEmpty();
        @endphp

        @if($hasFollowUps)
            <div class="activity-list">
                @foreach($unlinkedStudents as $studentUser)
                    <div class="review-row">
                        <span class="avatar">{{ mb_substr($studentUser->name, 0, 1) }}</span>
                        <div>
                            <strong>{{ $studentUser->name }}</strong>
                            <small>{{ $studentUser->email }} · Profil Siswa belum terhubung</small>
                        </div>
                        <x-badge :status="$studentUser->status ? 'Aktif' : 'Nonaktif'"/>
                        <a class="row-action" href="{{ route('admin.accounts.show', $studentUser) }}">Periksa</a>
                    </div>
                @endforeach

                @foreach($unlinkedMentors as $mentorUser)
                    <div class="review-row">
                        <span class="avatar">{{ mb_substr($mentorUser->name, 0, 1) }}</span>
                        <div>
                            <strong>{{ $mentorUser->name }}</strong>
                            <small>{{ $mentorUser->email }} · Profil Pembina belum terhubung</small>
                        </div>
                        <x-badge :status="$mentorUser->status ? 'Aktif' : 'Nonaktif'"/>
                        <a class="row-action" href="{{ route('admin.accounts.show', $mentorUser) }}">Periksa</a>
                    </div>
                @endforeach

                @foreach($inactiveUsers->take(4) as $inactiveUser)
                    <div class="review-row">
                        <span class="avatar">{{ mb_substr($inactiveUser->name, 0, 1) }}</span>
                        <div>
                            <strong>{{ $inactiveUser->name }}</strong>
                            <small>{{ $inactiveUser->email }} · Status Nonaktif</small>
                        </div>
                        <x-badge status="Nonaktif"/>
                        <form method="POST" action="{{ route('admin.accounts.toggle-status', $inactiveUser) }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="row-action" style="background: none; border: none; cursor: pointer; color: var(--primary);">Aktivasi</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty title="Semua akun telah siap" description="Tidak ada akun yang membutuhkan tindak lanjut atau perbaikan profil saat ini."/>
        @endif
    </section>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel">
            <div class="section-heading">
                <h2>Akun per Peran</h2>
                <a href="{{ route('admin.accounts.index') }}">Kelola akun</a>
            </div>
            @foreach($accountsPerRole as $roleKey => $item)
                <div class="distribution" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border, #e2e8f0);">
                    <div>
                        <strong>{{ $item['name'] }}</strong>
                        <div style="font-size: 12px; color: var(--muted, #64748b);">{{ $item['active'] }} aktif · {{ $item['inactive'] }} nonaktif</div>
                    </div>
                    <a href="{{ route('admin.accounts.index', ['role' => $roleKey]) }}" style="font-size: 18px; font-weight: 700; color: inherit; text-decoration: none;">
                        {{ $item['total'] }}
                    </a>
                </div>
            @endforeach
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Ringkasan Operasional</h2>
                <a href="{{ route('admin.reports.index') }}">Laporan lengkap</a>
            </div>
            <dl>
                <dt>Total Siswa Terdaftar</dt>
                <dd>{{ $stats['totalStudents'] }} siswa</dd>
                <dt>Total Pembina PKL</dt>
                <dd>{{ $stats['totalMentors'] }} pembina</dd>
                <dt>Total Tempat PKL</dt>
                <dd>{{ $stats['totalPlaces'] }} lokasi</dd>
                <dt>Penempatan PKL</dt>
                <dd>{{ $stats['totalPlacements'] }} penempatan</dd>
            </dl>
        </section>
    </div>
</div>

<section class="panel" style="margin-top: 24px;">
    <div class="section-heading">
        <div>
            <h2>Aktivitas Terbaru</h2>
            <p>Jejak tindakan pengguna dan sistem yang tersimpan di log audit.</p>
        </div>
        <a href="{{ route('admin.logs.index') }}">Lihat semua log</a>
    </div>

    @forelse($latestLogs as $log)
        <div class="review-row">
            <span class="round-icon"><x-icon name="file"/></span>
            <div>
                <strong>{{ $log->action }}</strong>
                <small>{{ $log->actor_name }} ({{ $roles[$log->role]['name'] ?? $log->role }}) · {{ $log->entity }}</small>
            </div>
            <span class="muted" style="font-size: 13px;">{{ $log->created_at->diffForHumans() }}</span>
        </div>
    @empty
        <x-empty title="Belum ada aktivitas tercatat" description="Log aktivitas sistem akan otomatis muncul di sini setelah aksi dilakukan."/>
    @endforelse
</section>
@endsection
