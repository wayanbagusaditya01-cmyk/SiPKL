@extends('layouts.portal')

@section('title', 'Akun Pengguna')

@section('content')
<x-page-heading title="Akun Pengguna" description="Buat akun dan atur peran, profil terhubung, serta status aksesnya.">
    <x-button :href="route('admin.accounts.create')"><x-icon name="plus"/>Buat akun</x-button>
</x-page-heading>

@if(session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif
@if($errors->any())
    <x-alert type="error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('admin.accounts.index') }}">
        <div class="field search-field">
            <label for="search">Cari akun</label>
            <input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Nama, email, atau telepon">
        </div>
        <div class="field">
            <label for="role-filter">Peran</label>
            <select id="role-filter" name="role">
                <option value="">Semua peran</option>
                @foreach($roles as $role => $identity)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $identity['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status-filter">Status akun</label>
            <select id="status-filter" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('admin.accounts.index') }}">Hapus filter</a>
    </form>

    @if($users->isNotEmpty())
        <div class="table-scroll">
            <table class="responsive-table">
                <caption class="sr-only">Daftar akun pengguna</caption>
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Email</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Profil Terhubung</th>
                        <th scope="col">Status</th>
                        <th scope="col">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $isSelf = auth()->id() === $user->id;
                            $hasProfile = ($user->role === 'siswa' && $user->student) || (in_array($user->role, ['pembina', 'pembina_sekolah', 'pembina_industri']) && $user->mentor) || in_array($user->role, ['admin', 'koordinator']);
                        @endphp
                        <tr>
                            <td data-label="Nama">
                                <strong>{{ $user->name }}</strong>
                                @if($isSelf)
                                    <span style="font-size: 11px; background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Akun Anda</span>
                                @endif
                            </td>
                            <td data-label="Email">{{ $user->email }}</td>
                            <td data-label="Peran">{{ $roles[$user->role]['name'] ?? $user->role }}</td>
                            <td data-label="Profil Terhubung">
                                @if($user->role === 'siswa')
                                    <x-badge :status="$user->student ? 'Aktif' : 'Menunggu'"/>
                                    <small style="display: block; font-size: 11px; color: var(--muted, #64748b);">{{ $user->student ? $user->student->nis : 'Belum ada profil' }}</small>
                                @elseif(in_array($user->role, ['pembina', 'pembina_sekolah', 'pembina_industri']))
                                    <x-badge :status="$user->mentor ? 'Aktif' : 'Menunggu'"/>
                                    <small style="display: block; font-size: 11px; color: var(--muted, #64748b);">{{ $user->mentor ? $user->mentor->type : 'Belum ada profil' }}</small>
                                @else
                                    <span style="color: var(--muted, #64748b); font-size: 13px;">Otomatis</span>
                                @endif
                            </td>
                            <td data-label="Status">
                                <x-badge :status="$user->status ? 'Aktif' : 'Nonaktif'"/>
                            </td>
                            <td data-label="Tindakan">
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <a class="row-action" href="{{ route('admin.accounts.show', $user) }}">Detail</a>
                                    <a class="row-action" href="{{ route('admin.accounts.edit', $user) }}">Ubah</a>

                                    @if(!$isSelf)
                                        <form method="POST" action="{{ route('admin.accounts.toggle-status', $user) }}" style="display: inline; margin: 0;">
                                            @csrf
                                            <button type="submit" class="row-action" style="background: none; border: none; cursor: pointer; color: {{ $user->status ? '#dc2626' : '#16a34a' }};">
                                                {{ $user->status ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.accounts.destroy', $user) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');" style="display: inline; margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="row-action" style="background: none; border: none; cursor: pointer; color: #dc2626;">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination"><span>{{ $users->total() }} akun</span>{{ $users->links() }}</div>
    @else
        <x-empty title="Akun tidak ditemukan" description="Ubah filter pencarian atau buat akun baru.">
            <x-button :href="route('admin.accounts.create')"><x-icon name="plus"/>Buat akun</x-button>
        </x-empty>
    @endif
</section>
@endsection