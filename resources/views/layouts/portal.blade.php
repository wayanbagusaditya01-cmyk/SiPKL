<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'SIPKL') | SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/book-open.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body>
    @php
        $isAdminPortal = request()->routeIs('admin.*');
        $isStudentPortal = request()->routeIs('student.*');
        $isMentorPortal = request()->routeIs('mentor.*');
        $portalMentor = $isMentorPortal ? auth()->user()->mentor : null;
        $portalRole = $isAdminPortal ? 'Admin' : ($isStudentPortal ? 'Siswa' : ($isMentorPortal ? ($portalMentor?->typeLabel() ?? (auth()->user()->role === 'pembina_industri' ? 'Pembina Industri' : 'Pembina Sekolah')) : 'Koordinator PKL'));
        $portalHome = $isAdminPortal ? route('admin.dashboard') : ($isStudentPortal ? route('student.portal.dashboard') : ($isMentorPortal ? route('mentor.dashboard') : route('coordinator.dashboard')));
        $portalNavigation = $isMentorPortal ? [
            ['label' => 'Ringkasan', 'url' => route('mentor.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('mentor.dashboard')],
            ['label' => $portalMentor?->isIndustry() ? 'Siswa Magang' : 'Siswa Bimbingan', 'url' => route('mentor.students.index'), 'icon' => 'users', 'active' => request()->routeIs('mentor.students.*')],
            ['label' => 'Penempatan', 'url' => route('mentor.placements.index'), 'icon' => 'place', 'active' => request()->routeIs('mentor.placements.*')],
            ['label' => 'Absensi', 'url' => route('mentor.attendance.index'), 'icon' => 'calendar', 'active' => request()->routeIs('mentor.attendance.*')],
            ['label' => 'Jurnal', 'url' => route('mentor.journals.index'), 'icon' => 'book', 'active' => request()->routeIs('mentor.journals.*')],
            ['label' => 'Penilaian', 'url' => route('mentor.grades.index'), 'icon' => 'grade', 'active' => request()->routeIs('mentor.grades.*')],
            ...($portalMentor?->isIndustry() && $portalMentor->place_id ? [['label' => 'Jadwal & Lokasi', 'url' => route('mentor.schedule.edit'), 'icon' => 'calendar', 'active' => request()->routeIs('mentor.schedule.*')]] : []),
            ['label' => 'Profil Saya', 'url' => route('mentor.profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('mentor.profile.*')],
        ] : ($isAdminPortal ? [
            ['label' => 'Ringkasan', 'url' => route('admin.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Akun Pengguna', 'url' => route('admin.accounts.index'), 'icon' => 'users', 'active' => request()->routeIs('admin.accounts.*')],
            ['label' => 'Hak Akses', 'url' => route('admin.permissions.index'), 'icon' => 'lock', 'active' => request()->routeIs('admin.permissions.*')],
            ['label' => 'Log Aktivitas', 'url' => route('admin.logs.index'), 'icon' => 'file', 'active' => request()->routeIs('admin.logs.*')],
            ['label' => 'Master Data', 'url' => route('admin.master-data.index'), 'icon' => 'place', 'active' => request()->routeIs('admin.master-data.*')],
            ['label' => 'Laporan Sistem', 'url' => route('admin.reports.index'), 'icon' => 'file', 'active' => request()->routeIs('admin.reports.*')],
            ['label' => 'Profil Admin', 'url' => route('admin.profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('admin.profile.*')],
        ] : ($isStudentPortal ? [
            ['label' => 'Ringkasan', 'url' => route('student.portal.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('student.portal.dashboard')],
            ['label' => 'Penempatan Saya', 'url' => route('student.portal.placement'), 'icon' => 'place', 'active' => request()->routeIs('student.portal.placement')],
            ['label' => 'Detail Tempat PKL', 'url' => route('student.portal.place'), 'icon' => 'place', 'active' => request()->routeIs('student.portal.place')],
            ['label' => 'Pembina Saya', 'url' => route('student.portal.mentors'), 'icon' => 'users', 'active' => request()->routeIs('student.portal.mentors')],
            ['label' => 'Absensi', 'url' => route('student.attendance.index'), 'icon' => 'calendar', 'active' => request()->routeIs('student.attendance.*')],
            ['label' => 'Jurnal Harian', 'url' => route('student.journals.index'), 'icon' => 'book', 'active' => request()->routeIs('student.journals.*')],
            ['label' => 'Nilai & Catatan', 'url' => route('student.portal.grade'), 'icon' => 'grade', 'active' => request()->routeIs('student.portal.grade')],
            ['label' => 'Notifikasi', 'url' => route('student.notifications.index'), 'icon' => 'bell', 'active' => request()->routeIs('student.notifications.*')],
            ['label' => 'Profil Saya', 'url' => route('student.profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('student.profile.*')],
        ] : [
            ['label' => 'Ringkasan', 'url' => route('coordinator.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('coordinator.dashboard')],
            ['label' => 'Data Siswa', 'url' => route('coordinator.siswa.index'), 'icon' => 'users', 'active' => request()->routeIs('coordinator.siswa.*')],
            ['label' => 'Data Pembina', 'url' => route('coordinator.pembina.index'), 'icon' => 'users', 'active' => request()->routeIs('coordinator.pembina.*')],
            ['label' => 'Tempat PKL', 'url' => route('coordinator.tempat.index'), 'icon' => 'place', 'active' => request()->routeIs('coordinator.tempat.*')],
            ['label' => 'Periode PKL', 'url' => route('coordinator.periode.index'), 'icon' => 'calendar', 'active' => request()->routeIs('coordinator.periode.*')],
            ['label' => 'Penempatan', 'url' => route('coordinator.penempatan.index'), 'icon' => 'place', 'active' => request()->routeIs('coordinator.penempatan.*')],
        ]));
        $person = auth()->user()->name;
        $initials = collect(explode(' ', trim($person)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    @endphp
    <a class="skip" href="#main">Lewati navigasi</a>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar" aria-label="Navigasi {{ $portalRole }}">
            <div class="sidebar-head">
                <a class="brand" href="{{ $portalHome }}"><x-icon name="book"/>SIPKL</a>
                <button type="button" class="icon-button sidebar-close" data-close-sidebar aria-label="Tutup navigasi"><x-icon name="close"/></button>
            </div>
            <p class="brand-description">Sistem Informasi<br>Praktik Kerja Lapangan</p>
            <div class="role-label">{{ $portalRole }}</div>
            <nav aria-label="Menu utama">
                <p class="nav-label">MENU UTAMA</p>
                @foreach($portalNavigation as $item)
                    <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif><x-icon :name="$item['icon']"/><span>{{ $item['label'] }}</span></a>
                @endforeach
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-profile">
                    <span class="avatar">{{ $initials }}</span>
                    <div><strong>{{ $person }}</strong><small>{{ $portalRole }}</small></div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout"><x-icon name="logout"/>Keluar</button>
                </form>
            </div>
        </aside>
        <div class="sidebar-overlay" data-close-sidebar hidden></div>
        <div class="app-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="icon-button menu-trigger" id="menu-trigger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi"><x-icon name="menu"/></button>
                    <span class="breadcrumb"><strong>SIPKL</strong><span>/</span>@yield('title', 'Admin')</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    @if($isStudentPortal)
                        @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())
                        <a href="{{ route('student.notifications.index') }}" class="icon-button" aria-label="Notifikasi ({{ $unreadNotificationCount }} belum dibaca)" style="position: relative;">
                            <x-icon name="bell"/>
                            @if($unreadNotificationCount > 0)
                                <span style="position: absolute; top: 4px; right: 4px; background: var(--error); color: #fff; font-size: 10px; font-weight: 700; border-radius: 9999px; min-width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; line-height: 1;">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                            @endif
                        </a>
                    @endif
                    <div class="topbar-person"><span>{{ $person }}</span><span class="avatar">{{ $initials }}</span></div>
                </div>
            </header>
            <main id="main" class="workspace">
                @if(session('success'))<x-alert type="success">{{ session('success') }}</x-alert>@endif
                @if($errors->any())
                    <x-alert type="error"><strong>Periksa kembali masukanmu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>