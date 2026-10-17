@extends('layouts.portal')

@section('title', 'Data Pembina')

@section('content')
<x-page-heading title="Data Pembina" description="Hubungkan akun Pembina sekolah dan industri ke data tugasnya.">
    <x-button :href="route('coordinator.pembina.create')"><x-icon name="plus"/>Tambah Pembina</x-button>
</x-page-heading>
<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('coordinator.pembina.index') }}">
        <div class="field search-field"><label for="search">Pencarian</label><input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Nama, email, NIP, atau jabatan"></div>
        <div class="field"><label for="type-filter">Tipe Pembina</label><select id="type-filter" name="type"><option value="">Semua tipe</option><option value="Sekolah" @selected(($filters['type'] ?? '') === 'Sekolah')>Sekolah</option><option value="Industri" @selected(($filters['type'] ?? '') === 'Industri')>Industri</option></select></div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('coordinator.pembina.index') }}">Hapus filter</a>
    </form>
    @if($mentors->isNotEmpty())
        <div class="table-scroll"><table class="responsive-table">
            <caption class="sr-only">Daftar Pembina PKL</caption>
            <thead><tr><th scope="col">Nama Pembina</th><th scope="col">Tipe</th><th scope="col">NIP / Jabatan</th><th scope="col">Email</th><th scope="col">Tempat PKL</th><th scope="col">Tindakan</th></tr></thead>
            <tbody>@foreach($mentors as $mentor)
                <tr>
                    <td data-label="Nama Pembina">{{ $mentor->user->name }}</td>
                    <td data-label="Tipe"><x-badge :status="$mentor->type"/></td>
                    <td data-label="NIP / Jabatan">{{ $mentor->type === 'Sekolah' ? ($mentor->nip ?: 'Belum diisi') : ($mentor->position ?: 'Belum diisi') }}</td>
                    <td data-label="Email">{{ $mentor->user->email }}</td>
                    <td data-label="Tempat PKL">{{ $mentor->place?->name ?? 'Sekolah' }}</td>
                    <td data-label="Tindakan"><a class="row-action" href="{{ route('coordinator.pembina.edit', $mentor) }}">Ubah data</a></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        <div class="pagination"><span>{{ $mentors->total() }} Pembina</span>{{ $mentors->links() }}</div>
    @else
        <x-empty title="Belum ada data Pembina" description="Admin perlu membuat akun Pembina aktif sebelum profilnya dapat dihubungkan.">
            <x-button :href="route('coordinator.pembina.create')"><x-icon name="plus"/>Tambah Pembina</x-button>
        </x-empty>
    @endif
</section>
@endsection