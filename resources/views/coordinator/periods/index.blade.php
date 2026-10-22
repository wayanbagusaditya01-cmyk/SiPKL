@extends('layouts.portal')

@section('title', 'Periode PKL')

@section('content')
<x-page-heading title="Periode PKL" description="Atur tahun ajaran dan rentang tanggal pelaksanaan PKL.">
    <x-button :href="route('coordinator.periode.create')"><x-icon name="plus"/>Tambah Periode</x-button>
</x-page-heading>
<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('coordinator.periode.index') }}">
        <div class="field search-field"><label for="search">Pencarian</label><input type="search" id="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Nama periode atau tahun ajaran"></div>
        <div class="field"><label for="status-filter">Status</label><select id="status-filter" name="status"><option value="">Semua status</option>@foreach(['Aktif', 'Selesai', 'Menunggu'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('coordinator.periode.index') }}">Hapus filter</a>
    </form>
    @if($periods->isNotEmpty())
        <div class="table-scroll"><table class="responsive-table">
            <caption class="sr-only">Daftar periode PKL</caption>
            <thead><tr><th scope="col">Nama Periode</th><th scope="col">Tahun Ajaran</th><th scope="col">Mulai</th><th scope="col">Selesai</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead>
            <tbody>@foreach($periods as $period)
                <tr>
                    <td data-label="Nama Periode">{{ $period->name }}</td>
                    <td data-label="Tahun Ajaran">{{ $period->academic_year }}</td>
                    <td data-label="Mulai">{{ $period->start_date->format('d-m-Y') }}</td>
                    <td data-label="Selesai">{{ $period->end_date->format('d-m-Y') }}</td>
                    <td data-label="Status"><x-badge :status="$period->status"/></td>
                    <td data-label="Tindakan"><x-action :href="route('coordinator.periode.edit', $period)" icon="edit">Ubah</x-action></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        <div class="pagination"><span>{{ $periods->total() }} periode</span>{{ $periods->links() }}</div>
    @else
        <x-empty title="Belum ada periode PKL" description="Buat periode sebelum menempatkan siswa.">
            <x-button :href="route('coordinator.periode.create')"><x-icon name="plus"/>Tambah Periode</x-button>
        </x-empty>
    @endif
</section>
@endsection