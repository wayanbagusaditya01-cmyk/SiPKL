@extends('layouts.portal')

@section('title', 'Notifikasi')

@section('content')
<x-page-heading title="Notifikasi" description="Pemberitahuan aktivitas jurnal, catatan pembina, dan nilai PKL.">
    @if($unreadCount > 0)
        <form method="post" action="{{ route('student.notifications.readAll') }}">
            @csrf
            <x-button type="submit" variant="secondary"><x-icon name="check"/>Tandai semua dibaca</x-button>
        </form>
    @endif
</x-page-heading>

<section class="panel">
    <div class="toolbar" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('student.notifications.index') }}" class="button {{ !$filterUnread ? 'primary' : 'secondary' }}" style="padding: 6px 14px; font-size: 13px;">Semua</a>
            <a href="{{ route('student.notifications.index', ['unread' => 1]) }}" class="button {{ $filterUnread ? 'primary' : 'secondary' }}" style="padding: 6px 14px; font-size: 13px;">
                Belum dibaca @if($unreadCount > 0)<span class="badge" style="margin-left: 6px; padding: 1px 7px; font-size: 11px;">{{ $unreadCount }}</span>@endif
            </a>
        </div>
    </div>

    @if($notifications->isNotEmpty())
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($notifications as $item)
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 16px; border: 1px solid var(--border); border-radius: 10px; background: {{ $item->isRead() ? 'var(--surface)' : 'var(--soft)' }}; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; gap: 14px; align-items: flex-start; flex: 1; min-width: 240px;">
                        <span class="round-icon" style="width: 40px; height: 40px; background: {{ $item->isRead() ? '#f3f4f6' : '#dbeafe' }}; color: {{ $item->isRead() ? 'var(--muted)' : 'var(--primary)' }};">
                            <x-icon :name="$item->type === 'grade' ? 'grade' : ($item->type === 'warning' ? 'info' : 'bell')"/>
                        </span>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <strong>{{ $item->title }}</strong>
                                @if(!$item->isRead())
                                    <span class="badge blue" style="font-size: 11px; padding: 2px 8px;">Baru</span>
                                @endif
                            </div>
                            <p style="color: var(--secondary); font-size: 13px; line-height: 1.5;">{{ $item->message }}</p>
                            <small class="muted">{{ $item->created_at->format('d-m-Y H:i') }} ({{ $item->created_at->diffForHumans() }})</small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                        @if($item->link)
                            <a href="{{ route('student.notifications.read', $item) }}" class="button secondary" style="padding: 6px 12px; font-size: 13px;">Buka</a>
                        @elseif(!$item->isRead())
                            <form method="post" action="{{ route('student.notifications.read', $item) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="row-action" style="background:none;border:none;cursor:pointer;color:var(--primary);font:inherit;font-size:13px;padding:0;">Tandai dibaca</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="pagination" style="margin-top: 20px;">
            <span>{{ $notifications->total() }} notifikasi</span>
            {{ $notifications->links() }}
        </div>
    @else
        <x-empty title="Tidak ada notifikasi" description="{{ $filterUnread ? 'Semua notifikasi sudah dibaca.' : 'Pemberitahuan aktivitas jurnal dan penilaian akan muncul di sini.' }}"/>
    @endif
</section>
@endsection
