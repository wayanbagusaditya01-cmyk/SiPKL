@extends('preview.base')
@section('title', $identity['menus'][$page])
@section('body')
@php
    $navIcons = ['dashboard' => 'dashboard', 'akun' => 'users', 'hak-akses' => 'lock', 'log-aktivitas' => 'file', 'master-data' => 'place', 'laporan' => 'file', 'profil' => 'user', 'siswa' => 'users', 'tempat' => 'place', 'tempat-pkl' => 'place', 'pembina' => 'users', 'periode' => 'calendar', 'penempatan' => 'place', 'absensi' => 'calendar', 'jurnal' => 'book', 'penilaian' => 'grade', 'nilai' => 'grade'];
    $person = auth()->user()->name;
    $initials = collect(explode(' ', trim($person)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $lookup = fn ($key, $id) => collect($data[$key])->firstWhere('id', $id);
    $link = function ($target, $params = []) use ($role) {
        if ($role === 'siswa' && auth()->user()->student()->exists()) {
            return match($target) {
                'dashboard' => route('student.portal.dashboard'),
                'penempatan' => route('student.portal.placement'),
                'tempat-pkl' => route('student.portal.place'),
                'pembina' => route('student.portal.mentors'),
                'absensi' => route('student.attendance.index'),
                'jurnal' => route('student.journals.index'),
                'nilai' => route('student.portal.grade'),
                'notifikasi' => route('student.notifications.index'),
                'profil' => route('student.profile.edit'),
                default => route('student.portal.dashboard'),
            }.($params ? '?'.http_build_query($params) : '');
        }

        if ($role === 'admin') {
            return match($target) {
                'dashboard' => route('admin.dashboard'),
                'akun' => route('admin.accounts.index'),
                'hak-akses' => route('admin.permissions.index'),
                'log-aktivitas' => route('admin.logs.index'),
                'master-data' => route('admin.master-data.index'),
                'laporan' => route('admin.reports.index'),
                'profil' => route('admin.profile.edit'),
                default => route('admin.dashboard'),
            }.($params ? '?'.http_build_query($params) : '');
        }

        if ($role === 'koordinator' && $target === 'siswa') {
            return route('coordinator.siswa.index').($params ? '?'.http_build_query($params) : '');
        }

        if ($role === 'koordinator' && $target === 'tempat') {
            return route('coordinator.tempat.index').($params ? '?'.http_build_query($params) : '');
        }

        if ($role === 'koordinator' && $target === 'penempatan') {
            return route('coordinator.penempatan.index').($params ? '?'.http_build_query($params) : '');
        }

        if ($role === 'koordinator' && $target === 'pembina') {
            return route('coordinator.pembina.index').($params ? '?'.http_build_query($params) : '');
        }

        if ($role === 'koordinator' && $target === 'periode') {
            return route('coordinator.periode.index').($params ? '?'.http_build_query($params) : '');
        }

        return route('preview.page', [$role, $target]).($params ? '?'.http_build_query($params) : '');
    };
@endphp
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigasi {{ $identity['name'] }}"><div class="sidebar-head"><a class="brand" href="{{ $link('dashboard') }}"><x-icon name="book"/>SIPKL</a><button type="button" class="icon-button sidebar-close" data-close-sidebar aria-label="Tutup navigasi"><x-icon name="close"/></button></div><p class="brand-description">Sistem Informasi<br>Praktik Kerja Lapangan</p><div class="role-label">{{ $identity['name'] }}</div><nav aria-label="Menu utama"><p class="nav-label">MENU UTAMA</p>@foreach($identity['menus'] as $key => $label)<a href="{{ $link($key) }}" @if($page === $key) aria-current="page" @endif><x-icon :name="$navIcons[$key]"/><span>{{ $label }}</span></a>@endforeach</nav><div class="sidebar-bottom"><div class="sidebar-profile"><span class="avatar">{{ $initials }}</span><div><strong>{{ $person }}</strong><small>{{ $role === 'pembina' ? 'Pembina Sekolah' : $identity['name'] }}</small></div></div><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout"><x-icon name="logout"/>Keluar</button></form></div></aside>
    <div class="sidebar-overlay" data-close-sidebar hidden></div>
    <div class="app-main"><header class="topbar"><div class="topbar-left"><button type="button" class="icon-button menu-trigger" id="menu-trigger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi"><x-icon name="menu"/></button><span class="breadcrumb"><strong>SIPKL</strong><span>/</span>{{ $identity['menus'][$page] }}</span></div><div class="topbar-person"><span>{{ $person }}</span><span class="avatar">{{ $initials }}</span></div></header>
    <main id="main" class="workspace"><div class="preview-bar"><span>Pratinjau antarmuka · Data contoh</span><div><button type="button" class="text-button" data-dialog="about-dialog">Tentang pratinjau</button></div></div>
    @if($role === 'siswa' && !auth()->user()->student()->exists())
        <div class="panel" style="margin-bottom: 20px; border-left: 4px solid var(--primary); display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div>
                <h3 style="margin: 0 0 4px 0; font-size: 15px;">Akun Siswa Belum Terhubung ke Database Portal</h3>
                <p style="margin: 0; font-size: 13px; color: var(--muted, #64748b);">Saat ini kamu masih menggunakan mode pratinjau dengan data simulasi. Aktifkan portal siswa untuk menyimpan absensi dan jurnal asli ke database.</p>
            </div>
            <form method="POST" action="{{ route('preview.siswa.activate', ['role' => 'siswa']) }}" style="margin: 0;">
                @csrf
                <x-button type="submit">Aktifkan Portal Siswa</x-button>
            </form>
        </div>
    @endif
    @if(session('success'))<x-alert type="success">{{ session('success') }}</x-alert>@endif
    @if($errors->any())<x-alert type="error"><strong>Periksa masukanmu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
    @if($page === 'dashboard') @include('preview.pages.dashboard')
    @elseif($role === 'siswa' && in_array($page, ['penempatan', 'tempat-pkl', 'pembina', 'nilai'])) @include('preview.pages.student-detail')
    @elseif($page === 'jurnal') @include('preview.pages.journal')
    @elseif($page === 'penilaian') @include('preview.pages.grade')
    @elseif($page === 'profil') @include('preview.pages.profile')
    @elseif($page === 'hak-akses') @include('preview.pages.permissions')
    @elseif($page === 'master-data') @include('preview.pages.reference')
    @elseif($page === 'laporan') @include('preview.pages.reports')
    @else @include('preview.pages.table')
    @endif
    <footer class="workspace-footer">SIPKL · Praktik kerja, progres yang tercatat.<span>Pratinjau tanpa data produksi</span></footer></main></div>
</div>
@endsection
