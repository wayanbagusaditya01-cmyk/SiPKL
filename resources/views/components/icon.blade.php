@props(['name' => 'book'])
@php
    $paths = [
        'book' => '<path d="M12 5c-3-2-7-2-10-1v15c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1Z"/><path d="M12 5v15"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'users' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5"/>',
        'place' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h1m6 0h1M8 11h1m6 0h1M10 21v-6h4v6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18"/>',
        'file' => '<path d="M14 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V10Z"/><path d="M14 3v7h7M7 14h10m-10 3h7"/>',
        'grade' => '<path d="m12 3 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1Z"/>',
        'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>',
        'logout' => '<path d="M9 4H4v16h5m5-12 4 4-4 4M8 12h13"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'plus' => '<path d="M12 4v16M4 12h16"/>',
        'search' => '<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
        'check' => '<path d="m4 12 5 5L20 6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/>',
        'back' => '<path d="m10 5-7 7 7 7M3 12h18"/>',
    ];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['file'] !!}</svg>
