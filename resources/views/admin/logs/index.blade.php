@extends('layouts.portal')

@section('title', 'Log Aktivitas')

@section('content')
<x-page-heading title="Log Aktivitas" description="Jejak aktivitas, autentikasi, dan perubahan data yang tercatat di database sistem.">
    <form method="POST" action="{{ route('admin.logs.clear') }}" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan log aktivitas yang lebih lama dari 30 hari?');" style="margin: 0;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="days" value="30">
        <x-button variant="secondary" type="submit">Bersihkan Log > 30 Hari</x-button>
    </form>
</x-page-heading>

@if(session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif

<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('admin.logs.index') }}">
        <div class="field search-field">
            <label for="search">Pencarian</label>
            <input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Pelaku, aksi, entitas, atau detail">
        </div>

        <div class="field">
            <label for="role-filter">Peran</label>
            <select id="role-filter" name="role">
                <option value="">Semua peran</option>
                @foreach($roles as $roleKey => $roleData)
                    <option value="{{ $roleKey }}" @selected(($filters['role'] ?? '') === $roleKey)>{{ $roleData['name'] }}</option>
                @endforeach
            </select>
        </div>

        @if(!empty($actions))
            <div class="field">
                <label for="action-filter">Tindakan</label>
                <select id="action-filter" name="action">
                    <option value="">Semua tindakan</option>
                    @foreach($actions as $actionItem)
                        <option value="{{ $actionItem }}" @selected(($filters['action'] ?? '') === $actionItem)>{{ $actionItem }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="field">
            <label for="from-filter">Dari tanggal</label>
            <input type="date" id="from-filter" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>

        <div class="field">
            <label for="to-filter">Sampai tanggal</label>
            <input type="date" id="to-filter" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>

        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('admin.logs.index') }}">Hapus filter</a>
    </form>

    @if($logs->isNotEmpty())
        <div class="table-scroll">
            <table class="responsive-table">
                <caption class="sr-only">Daftar log aktivitas</caption>
                <thead>
                    <tr>
                        <th scope="col" style="min-width: 140px;">Waktu</th>
                        <th scope="col">Akun / Pelaku</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Tindakan</th>
                        <th scope="col">Entitas</th>
                        <th scope="col">Detail Aktivitas</th>
                        <th scope="col">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td data-label="Waktu">
                                <div>{{ $log->created_at->format('d M Y, H:i') }}</div>
                                <small style="color: var(--muted, #64748b);">{{ $log->created_at->diffForHumans() }}</small>
                            </td>
                            <td data-label="Akun / Pelaku">
                                <strong>{{ $log->actor_name }}</strong>
                                @if($log->user)
                                    <small style="display: block; color: var(--muted, #64748b);">{{ $log->user->email }}</small>
                                @endif
                            </td>
                            <td data-label="Peran">
                                <x-badge :status="$roles[$log->role]['name'] ?? $log->role"/>
                            </td>
                            <td data-label="Tindakan">
                                <span style="font-weight: 600;">{{ $log->action }}</span>
                            </td>
                            <td data-label="Entitas">{{ $log->entity }}</td>
                            <td data-label="Detail Aktivitas">
                                <div style="font-size: 13px; color: var(--text, #1e293b); max-width: 320px; word-break: break-word;">
                                    {{ $log->detail ?: '-' }}
                                </div>
                            </td>
                            <td data-label="IP Address">
                                <code style="font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">{{ $log->ip_address ?: '127.0.0.1' }}</code>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <span>Total {{ $logs->total() }} log aktivitas</span>
            {{ $logs->links() }}
        </div>
    @else
        <x-empty title="Tidak ada log aktivitas" description="Tidak ada rekaman log yang cocok dengan filter pencarian saat ini.">
            <x-button variant="secondary" :href="route('admin.logs.index')">Hapus filter</x-button>
        </x-empty>
    @endif
</section>
@endsection
