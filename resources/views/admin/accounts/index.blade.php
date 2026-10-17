@extends('layouts.portal')

@section('title', 'Akun Pengguna')

@section('content')
<x-page-heading title="Akun Pengguna" description="Buat akun dan atur peran serta status aksesnya.">
    <x-button :href="route('admin.accounts.create')"><x-icon name="plus"/>Buat akun</x-button>
</x-page-heading>

<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('admin.accounts.index') }}">
        <div class="field search-field">
            <label for="search">Cari akun</label>
            <input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Nama atau email">
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
                <thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Peran</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td data-label="Nama">{{ $user->name }}</td>
                            <td data-label="Email">{{ $user->email }}</td>
                            <td data-label="Peran">{{ $roles[$user->role]['name'] ?? $user->role }}</td>
                            <td data-label="Status"><x-badge :status="$user->status ? 'Aktif' : 'Nonaktif'"/></td>
                            <td data-label="Tindakan"><a class="row-action" href="{{ route('admin.accounts.edit', $user) }}">Ubah akun</a></td>
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