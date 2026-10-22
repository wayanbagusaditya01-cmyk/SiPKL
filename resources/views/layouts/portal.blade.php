<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'SIPKL') | SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/book-open.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
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
        $portalNavigation = collect($isMentorPortal ? [
            ['group' => 'Utama', 'label' => 'Ringkasan', 'url' => route('mentor.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('mentor.dashboard')],
            ['group' => 'Bimbingan', 'label' => $portalMentor?->isIndustry() ? 'Siswa Magang' : 'Siswa Bimbingan', 'url' => route('mentor.students.index'), 'icon' => 'users', 'active' => request()->routeIs('mentor.students.*')],
            ['group' => 'Bimbingan', 'label' => 'Penempatan', 'url' => route('mentor.placements.index'), 'icon' => 'briefcase', 'active' => request()->routeIs('mentor.placements.*')],
            ['group' => 'Bimbingan', 'label' => 'Absensi', 'url' => route('mentor.attendance.index'), 'icon' => 'calendar', 'active' => request()->routeIs('mentor.attendance.*')],
            ['group' => 'Bimbingan', 'label' => 'Jurnal', 'url' => route('mentor.journals.index'), 'icon' => 'book', 'active' => request()->routeIs('mentor.journals.*')],
            ['group' => 'Bimbingan', 'label' => 'Penilaian', 'url' => route('mentor.grades.index'), 'icon' => 'grade', 'active' => request()->routeIs('mentor.grades.*')],
            ...($portalMentor?->isIndustry() && $portalMentor->place_id ? [['group' => 'Pengaturan', 'label' => 'Jadwal & Lokasi', 'url' => route('mentor.schedule.edit'), 'icon' => 'clock', 'active' => request()->routeIs('mentor.schedule.*')]] : []),
            ['group' => 'Pengaturan', 'label' => 'Profil Saya', 'url' => route('mentor.profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('mentor.profile.*')],
        ] : ($isAdminPortal ? [
            ['group' => 'Utama', 'label' => 'Ringkasan', 'url' => route('admin.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('admin.dashboard')],
            ['group' => 'Pengguna', 'label' => 'Akun Pengguna', 'url' => route('admin.accounts.index'), 'icon' => 'users', 'active' => request()->routeIs('admin.accounts.*')],
            ['group' => 'Pengguna', 'label' => 'Hak Akses', 'url' => route('admin.permissions.index'), 'icon' => 'shield', 'active' => request()->routeIs('admin.permissions.*')],
            ['group' => 'Sistem', 'label' => 'Master Data', 'url' => route('admin.master-data.index'), 'icon' => 'database', 'active' => request()->routeIs('admin.master-data.*')],
            ['group' => 'Sistem', 'label' => 'Log Aktivitas', 'url' => route('admin.logs.index'), 'icon' => 'clipboard', 'active' => request()->routeIs('admin.logs.*')],
            ['group' => 'Sistem', 'label' => 'Laporan Sistem', 'url' => route('admin.reports.index'), 'icon' => 'chart', 'active' => request()->routeIs('admin.reports.*')],
            ['group' => 'Akun', 'label' => 'Profil Admin', 'url' => route('admin.profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('admin.profile.*')],
        ] : ($isStudentPortal ? [
            ['group' => 'Utama', 'label' => 'Ringkasan', 'url' => route('student.portal.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('student.portal.dashboard')],
            ['group' => 'PKL Saya', 'label' => 'Penempatan Saya', 'url' => route('student.portal.placement'), 'icon' => 'briefcase', 'active' => request()->routeIs('student.portal.placement')],
            ['group' => 'PKL Saya', 'label' => 'Detail Tempat PKL', 'url' => route('student.portal.place'), 'icon' => 'place', 'active' => request()->routeIs('student.portal.place')],
            ['group' => 'PKL Saya', 'label' => 'Pembina Saya', 'url' => route('student.portal.mentors'), 'icon' => 'users', 'active' => request()->routeIs('student.portal.mentors')],
            ['group' => 'Kegiatan', 'label' => 'Absensi', 'url' => route('student.attendance.index'), 'icon' => 'calendar', 'active' => request()->routeIs('student.attendance.*')],
            ['group' => 'Kegiatan', 'label' => 'Jurnal Harian', 'url' => route('student.journals.index'), 'icon' => 'book', 'active' => request()->routeIs('student.journals.*')],
            ['group' => 'Kegiatan', 'label' => 'Nilai & Catatan', 'url' => route('student.portal.grade'), 'icon' => 'grade', 'active' => request()->routeIs('student.portal.grade')],
            ['group' => 'Akun', 'label' => 'Notifikasi', 'url' => route('student.notifications.index'), 'icon' => 'bell', 'active' => request()->routeIs('student.notifications.*')],
            ['group' => 'Akun', 'label' => 'Profil Saya', 'url' => route('student.profile.edit'), 'icon' => 'user', 'active' => request()->routeIs('student.profile.*')],
        ] : [
            ['group' => 'Utama', 'label' => 'Ringkasan', 'url' => route('coordinator.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('coordinator.dashboard')],
            ['group' => 'Data Master', 'label' => 'Data Siswa', 'url' => route('coordinator.siswa.index'), 'icon' => 'users', 'active' => request()->routeIs('coordinator.siswa.*')],
            ['group' => 'Data Master', 'label' => 'Data Pembina', 'url' => route('coordinator.pembina.index'), 'icon' => 'user', 'active' => request()->routeIs('coordinator.pembina.*')],
            ['group' => 'Data Master', 'label' => 'Tempat PKL', 'url' => route('coordinator.tempat.index'), 'icon' => 'place', 'active' => request()->routeIs('coordinator.tempat.*')],
            ['group' => 'Data Master', 'label' => 'Periode PKL', 'url' => route('coordinator.periode.index'), 'icon' => 'calendar', 'active' => request()->routeIs('coordinator.periode.*')],
            ['group' => 'Operasional', 'label' => 'Penempatan', 'url' => route('coordinator.penempatan.index'), 'icon' => 'briefcase', 'active' => request()->routeIs('coordinator.penempatan.*')],
        ])));
        $currentNavigation = $portalNavigation->firstWhere('active', true);
        $person = auth()->user()->name;
        $initials = collect(explode(' ', trim($person)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    @endphp
    <a class="skip" href="#main">Lewati navigasi</a>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar" aria-label="Navigasi {{ $portalRole }}">
            <div class="sidebar-head">
                <a class="brand" href="{{ $portalHome }}">
                    <span class="brand__logo"><img src="{{ asset('assets/logo-smk.png') }}" alt="" width="30" height="30"></span>
                    <span class="brand__text"><strong>SIPKL</strong><small>SMK TI Bali Global Badung</small></span>
                </a>
                <button type="button" class="icon-button sidebar-close" data-close-sidebar aria-label="Tutup navigasi"><x-icon name="close"/></button>
            </div>
            <p class="sidebar-portal"><span aria-hidden="true"></span>Portal {{ $portalRole }}</p>
            <nav aria-label="Menu utama">
                @foreach($portalNavigation->groupBy('group') as $group => $items)
                    <p class="nav-label">{{ $group }}</p>
                    @foreach($items as $item)
                        <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif><x-icon :name="$item['icon']"/><span>{{ $item['label'] }}</span></a>
                    @endforeach
                @endforeach
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-profile">
                    <span class="avatar">{{ $initials }}</span>
                    <div><strong>{{ $person }}</strong><small>{{ $portalRole }}</small></div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout" title="Keluar"><x-icon name="logout"/><span>Keluar</span></button>
                </form>
            </div>
        </aside>
        <div class="sidebar-overlay" data-close-sidebar hidden></div>
        <div class="app-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="icon-button menu-trigger" id="menu-trigger" aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi"><x-icon name="menu"/></button>
                    <nav class="breadcrumb" aria-label="Lokasi halaman">
                        <a href="{{ $portalHome }}">Portal {{ $portalRole }}</a>
                        @if($currentNavigation && $currentNavigation['group'] !== 'Utama')<x-icon name="chevron"/><span>{{ $currentNavigation['group'] }}</span>@endif
                        <x-icon name="chevron"/><strong>@yield('title', 'Admin')</strong>
                    </nav>
                </div>
                <div class="topbar-actions">
                    @if($isStudentPortal)
                        @php
                            $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
                            $recentNotifications = auth()->user()->notifications()->limit(5)->get();
                        @endphp
                        <div class="popover-wrap" data-popover>
                            <button type="button" class="icon-button topbar-bell" data-popover-trigger aria-expanded="false" aria-controls="notification-popover" aria-label="Notifikasi ({{ $unreadNotificationCount }} belum dibaca)">
                                <x-icon name="bell"/>
                                @if($unreadNotificationCount > 0)<span class="topbar-bell__count">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
                            </button>
                            <div class="popover popover--notifications" id="notification-popover" data-popover-panel hidden>
                                <div class="popover__head">
                                    <strong>Notifikasi</strong>
                                    @if($unreadNotificationCount > 0)
                                        <form method="post" action="{{ route('student.notifications.readAll') }}">
                                            @csrf
                                            <button type="submit" class="popover__link">Tandai semua dibaca</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="popover__list">
                                    @forelse($recentNotifications as $notification)
                                        <form method="post" action="{{ route('student.notifications.read', $notification) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="notif-item {{ $notification->isRead() ? '' : 'is-unread' }}">
                                                <span class="notif-item__icon" aria-hidden="true"><x-icon :name="$notification->type === 'grade' ? 'grade' : ($notification->type === 'jurnal' ? 'book' : 'bell')"/></span>
                                                <span class="notif-item__body">
                                                    <strong>{{ $notification->title }}</strong>
                                                    <span>{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</span>
                                                    <small>{{ $notification->created_at->diffForHumans() }}</small>
                                                </span>
                                            </button>
                                        </form>
                                    @empty
                                        <p class="popover__empty">Belum ada notifikasi.</p>
                                    @endforelse
                                </div>
                                <a class="popover__footer" href="{{ route('student.notifications.index') }}">Lihat semua notifikasi</a>
                            </div>
                        </div>
                    @endif
                    @php
                        $profileRoute = match (true) {
                            $isAdminPortal => 'admin.profile.edit',
                            $isStudentPortal => 'student.profile.edit',
                            $isMentorPortal => 'mentor.profile.edit',
                            default => null,
                        };
                    @endphp
                    <div class="popover-wrap" data-popover>
                        <button type="button" class="account-trigger" data-popover-trigger aria-expanded="false" aria-controls="account-popover" aria-haspopup="true">
                            <span class="account-trigger__name">{{ $person }}</span>
                            <span class="avatar">{{ $initials }}</span>
                        </button>
                        <div class="popover popover--account" id="account-popover" data-popover-panel hidden>
                            <div class="account-card">
                                <span class="avatar avatar--lg">{{ $initials }}</span>
                                <div>
                                    <strong>{{ $person }}</strong>
                                    <small>{{ auth()->user()->email }}</small>
                                    <span class="badge blue">{{ $portalRole }}</span>
                                </div>
                            </div>
                            <nav class="popover__menu" aria-label="Menu akun">
                                <a href="{{ $portalHome }}"><x-icon name="dashboard"/>Ringkasan</a>
                                @if($profileRoute)<a href="{{ route($profileRoute) }}"><x-icon name="user"/>Profil saya</a>@endif
                                @if($isStudentPortal)<a href="{{ route('student.notifications.index') }}"><x-icon name="bell"/>Notifikasi</a>@endif
                            </nav>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="popover__logout"><x-icon name="logout"/>Keluar</button>
                            </form>
                        </div>
                    </div>
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