@extends('layouts.portal')

@section('title', 'Penempatan PKL')

@section('content')
<x-page-heading title="Penempatan PKL" description="Tentukan tempat PKL untuk setiap siswa.">
    <x-button :href="route('coordinator.penempatan.create')"><x-icon name="plus"/>Tambah Penempatan</x-button>
</x-page-heading>
<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('coordinator.penempatan.index') }}">
        <div class="field search-field"><label for="search">Pencarian</label><input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Nama siswa, NIS, atau tempat"></div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('coordinator.penempatan.index') }}">Hapus filter</a>
    </form>
    @if($placements->isNotEmpty())
        <div class="table-scroll"><table class="responsive-table">
            <caption class="sr-only">Daftar penempatan PKL</caption>
            <thead><tr><th scope="col">NIS</th><th scope="col">Nama siswa</th><th scope="col">Tempat PKL</th><th scope="col">Periode</th><th scope="col">Pembina sekolah</th><th scope="col">Pembina industri</th><th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead>
            <tbody>@foreach($placements as $placement)
                <tr>
                    <td data-label="NIS">{{ $placement->student->nis }}</td>
                    <td data-label="Nama siswa">{{ $placement->student->name }}</td>
                    <td data-label="Tempat PKL">{{ $placement->place->name }}</td>
                    <td data-label="Periode">{{ $placement->period?->name ?? 'Belum diatur' }}</td>
                    <td data-label="Pembina sekolah">{{ $placement->schoolMentor?->user?->name ?? 'Belum diatur' }}</td>
                    <td data-label="Pembina industri">{{ $placement->industryMentor?->user?->name ?? 'Belum diatur' }}</td>
                    <td data-label="Tanggal">{{ $placement->placed_at?->format('d-m-Y') ?? 'Belum diisi' }}</td>
                    <td data-label="Status"><x-badge :status="$placement->status"/></td>
                    <td data-label="Tindakan"><a class="row-action" href="{{ route('coordinator.penempatan.edit', $placement) }}">Ubah data</a></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        <div class="pagination"><span>{{ $placements->total() }} penempatan</span>{{ $placements->links() }}</div>
    @else
        <x-empty title="Belum ada penempatan" description="Buat daftar tempat PKL terlebih dahulu, lalu tetapkan tempat untuk siswa.">
            <x-button :href="route('coordinator.penempatan.create')"><x-icon name="plus"/>Tambah Penempatan</x-button>
        </x-empty>
    @endif
</section>
@endsection