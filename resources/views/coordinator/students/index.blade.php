@extends('layouts.portal')

@section('title', 'Data Siswa')

@section('content')
<x-page-heading title="Data Siswa" description="Kelola profil siswa PKL terpisah dari akun login.">
    <x-button :href="route('coordinator.siswa.create')"><x-icon name="plus"/>Tambah siswa</x-button>
</x-page-heading>

<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('coordinator.siswa.index') }}">
        <div class="field search-field">
            <label for="search">Cari siswa</label>
            <input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="NIS, nama, kelas, atau jurusan">
        </div>
        <div class="field">
            <label for="class-filter">Kelas</label>
            <select id="class-filter" name="class">
                <option value="">Semua kelas</option>
                @foreach($classOptions as $classOption)
                    <option value="{{ $classOption }}" @selected(($filters['class'] ?? '') === $classOption)>{{ $classOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="major-filter">Jurusan</label>
            <select id="major-filter" name="major">
                <option value="">Semua jurusan</option>
                @foreach($majorOptions as $majorOption)
                    <option value="{{ $majorOption }}" @selected(($filters['major'] ?? '') === $majorOption)>{{ $majorOption }}</option>
                @endforeach
            </select>
        </div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('coordinator.siswa.index') }}">Hapus filter</a>
    </form>

    @if($students->isNotEmpty())
        <div class="table-scroll">
            <table class="responsive-table">
                <caption class="sr-only">Daftar profil siswa PKL</caption>
                <thead><tr><th scope="col">NIS</th><th scope="col">Nama siswa</th><th scope="col">Kelas</th><th scope="col">Jurusan</th><th scope="col">Tempat PKL</th><th scope="col">Email</th><th scope="col">Akun</th><th scope="col">Tindakan</th></tr></thead>
                <tbody>
                    @foreach($students as $student)
                        <tr>
                            <td data-label="NIS">{{ $student->nis }}</td>
                            <td data-label="Nama siswa">{{ $student->name }}</td>
                            <td data-label="Kelas">{{ $student->class }}</td>
                            <td data-label="Jurusan">{{ $student->major }}</td>
                            <td data-label="Tempat PKL">{{ $student->placement?->place?->name ?? 'Belum ditempatkan' }}</td>
                            <td data-label="Email">{{ $student->email ?: 'Belum diisi' }}</td>
                            <td data-label="Akun">{{ $student->user?->email ?? 'Belum ditautkan' }}</td>
                            <td data-label="Tindakan"><x-action :href="route('coordinator.siswa.edit', $student)" icon="edit">Ubah</x-action></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination"><span>{{ $students->total() }} siswa</span>{{ $students->links() }}</div>
    @else
        <x-empty title="Belum ada data siswa" description="Tambahkan profil siswa sekarang. Data Excel dapat dicocokkan sebelum impor.">
            <x-button :href="route('coordinator.siswa.create')"><x-icon name="plus"/>Tambah siswa</x-button>
        </x-empty>
    @endif
</section>
@endsection