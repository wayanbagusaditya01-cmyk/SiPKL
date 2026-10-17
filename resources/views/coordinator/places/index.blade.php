@extends('layouts.portal')

@section('title', 'Tempat PKL')

@section('content')
<x-page-heading title="Tempat PKL" description="Kelola daftar tempat yang dapat dipilih pada Penempatan.">
    <x-button :href="route('coordinator.tempat.create')"><x-icon name="plus"/>Tambah Tempat</x-button>
</x-page-heading>

<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('coordinator.tempat.index') }}">
        <div class="field search-field"><label for="search">Pencarian</label><input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Cari nama, alamat, atau kontak"></div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('coordinator.tempat.index') }}">Hapus filter</a>
    </form>
    @if($places->isNotEmpty())
        <div class="table-scroll"><table class="responsive-table">
            <caption class="sr-only">Daftar tempat PKL</caption>
            <thead><tr><th scope="col">Nama tempat</th><th scope="col">Alamat</th><th scope="col">Koordinat</th><th scope="col">Kontak</th><th scope="col">Tindakan</th></tr></thead>
            <tbody>@foreach($places as $place)
                <tr>
                    <td data-label="Nama tempat">{{ $place->name }}</td>
                    <td data-label="Alamat">{{ $place->address ?: 'Belum diisi' }}</td>
                    <td data-label="Koordinat">{{ $place->latitude && $place->longitude ? $place->latitude.', '.$place->longitude : 'Belum diisi' }}</td>
                    <td data-label="Kontak">{{ $place->contact ?: $place->email ?: 'Belum diisi' }}</td>
                    <td data-label="Tindakan"><a class="row-action" href="{{ route('coordinator.tempat.edit', $place) }}">Ubah data</a></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        <div class="pagination"><span>{{ $places->total() }} tempat</span>{{ $places->links() }}</div>
    @else
        <x-empty title="Belum ada tempat PKL" description="Tambahkan tempat agar Koordinator dapat menggunakannya saat membuat penempatan.">
            <x-button :href="route('coordinator.tempat.create')"><x-icon name="plus"/>Tambah Tempat</x-button>
        </x-empty>
    @endif
</section>
@endsection