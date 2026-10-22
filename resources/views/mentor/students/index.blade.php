@extends('layouts.portal')

@section('title', $mentor->isIndustry() ? 'Siswa Magang' : 'Siswa Bimbingan')

@section('content')
@php($partnerLabel = $mentor->isIndustry() ? 'Pembina Sekolah' : 'Pembina Industri')
<x-page-heading :title="$mentor->isIndustry() ? 'Siswa Magang' : 'Siswa Bimbingan'" :description="'Daftar siswa yang berada di bawah bimbingan Anda sebagai '.$mentor->typeLabel().'.'"/>

<section class="panel table-panel">
    <form class="toolbar" method="get" action="{{ route('mentor.students.index') }}">
        <div class="field search-field">
            <label for="student-search">Cari siswa</label>
            <input type="search" id="student-search" name="q" value="{{ $q }}" maxlength="100" placeholder="Nama, NIS, atau tempat PKL">
        </div>
        <x-button type="submit" variant="secondary">Terapkan</x-button>
        <a class="reset-link" href="{{ route('mentor.students.index') }}">Hapus filter</a>
    </form>

    @if($placements->isNotEmpty())
        <div class="table-scroll">
            <table class="responsive-table">
                <caption class="sr-only">Daftar siswa bimbingan</caption>
                <thead>
                    <tr>
                        <th scope="col">Siswa</th>
                        <th scope="col">NIS</th>
                        <th scope="col">Kelas</th>
                        <th scope="col">Tempat PKL</th>
                        <th scope="col">{{ $partnerLabel }}</th>
                        <th scope="col">Periode</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($placements as $placement)
                        <tr>
                            <td data-label="Siswa">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="avatar">{{ mb_substr($placement->student->name ?? '?', 0, 1) }}</span>
                                    <div>
                                        <strong>{{ $placement->student->name ?? 'N/A' }}</strong>
                                        <small class="muted">{{ $placement->student->email ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td data-label="NIS">{{ $placement->student->nis ?? '—' }}</td>
                            <td data-label="Kelas">{{ $placement->student->class ?? '—' }}</td>
                            <td data-label="Tempat PKL">{{ $placement->place->name ?? '—' }}</td>
                            <td data-label="{{ $partnerLabel }}">{{ ($mentor->isIndustry() ? $placement->schoolMentor?->user?->name : $placement->industryMentor?->user?->name) ?? 'Belum ditetapkan' }}</td>
                            <td data-label="Periode">{{ $placement->period->name ?? 'Belum diatur' }}</td>
                            <td data-label="Status"><x-badge :status="$placement->status"/></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <span>{{ $placements->count() }} siswa</span>
        </div>
    @else
        <x-empty title="Belum ada siswa bimbingan" description="Koordinator PKL belum menugaskan siswa kepada Anda."/>
    @endif
</section>
@endsection
