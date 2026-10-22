@extends('preview.base')
@section('title', $identity['menus'][$page])
@section('body')
@php
    $navIcons = ['dashboard' => 'dashboard', 'akun' => 'users', 'hak-akses' => 'lock', 'log-aktivitas' => 'file', 'master-data' => 'place', 'laporan' => 'file', 'profil' => 'user', 'siswa' => 'users', 'tempat' => 'place', 'tempat-pkl' => 'place', 'pembina' => 'users', 'periode' => 'calendar', 'penempatan' => 'place', 'absensi' => 'calendar', 'jurnal' => 'book', 'penilaian' => 'grade', 'nilai' => 'grade'];
    $person = $data['profiles'][$role]['name'] ?? $identity['person'];
    $lookup = fn ($key, $id) => collect($data[$key])->firstWhere('id', $id);
    $link = fn ($target, $params = []) => route('preview.page', [$role, $target]) . ($params ? '?'.http_build_query($params) : '');
@endphp
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigasi {{ $identity['name'] }}"><div class="sidebar-head"><a class="brand" href="{{ $link('dashboard') }}"><x-icon name="book"/>SIPKL</a><button type="button" class="icon-button sidebar-close" data-close-sidebar aria-label="Tutup navigasi"><x-icon name="close"/></button></div><p class="brand-description">Sistem Informasi<br>Praktik Kerja Lapangan</p><div class="role-label">{{ $identity['name'] }}</div><nav aria-label="Menu utama"><p class="nav-label">MENU UTAMA</p>@foreach($identity['menus'] as $key => $label)<a href="{{ $link($key) }}" @if($page === $key) aria-current="page" @endif><x-icon :name="$navIcons[$key]"/><span>{{ $label }}</span></a>@endforeach</nav><div class="sidebar-bottom"><div class="sidebar-profile"><span class="avatar">{{ $identity['initials'] }}</span><div><strong>{{ $person }}</strong><small>{{ $role === 'pembina' ? 'Pembina Sekolah' : $identity['name'] }}</small></div></div><a href="{{ route('login') }}" class="logout"><x-icon name="logout"/>Keluar pratinjau</a></div></aside>
    <div class="sidebar-overlay" data-close-sidebar hidden></div>
    <div class="app-main"><header class="topbar"><div class="topbar-left"><button type="button" class="icon-button menu-trigger" id="menu-trigger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi"><x-icon name="menu"/></button><span class="breadcrumb"><strong>SIPKL</strong><span>/</span>{{ $identity['menus'][$page] }}</span></div><div class="topbar-person"><span>{{ $person }}</span><span class="avatar">{{ $identity['initials'] }}</span></div></header>
    <main id="main" class="workspace"><div class="preview-bar"><span>Pratinjau antarmuka · Data contoh</span><div><a href="{{ route('login') }}">Ganti peran</a><button type="button" class="text-button" data-dialog="about-dialog">Tentang pratinjau</button></div></div>
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
