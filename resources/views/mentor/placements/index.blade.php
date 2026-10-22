@extends('layouts.portal')

@section('title', 'Penempatan')

@section('content')
<x-page-heading title="Penempatan Siswa" :description="'Tempat, periode, dan pasangan pembina untuk setiap siswa yang Anda bimbing sebagai '.$mentor->typeLabel().'.'"/>

@forelse($placements as $placement)
    <section class="panel form-width" style="margin-bottom: 24px;">
        <div class="section-heading">
            <div>
                <h2>{{ $placement->student?->name ?? 'Siswa' }}</h2>
                <p>{{ $placement->student?->nis }} · {{ $placement->student?->class }}</p>
            </div>
            <x-badge :status="$placement->status"/>
        </div>
        <dl>
            <dt>Tempat PKL</dt>
            <dd>{{ $placement->place?->name ?? 'Belum ditetapkan' }}</dd>
            <dt>Alamat</dt>
            <dd>{{ $placement->place?->address ?: 'Belum diisi' }}</dd>
            <dt>Kontak tempat</dt>
            <dd>{{ $placement->place?->contact ?: 'Belum diisi' }}</dd>
            <dt>Periode</dt>
            <dd>{{ $placement->period?->name ?? 'Belum diatur' }}@if($placement->period) ({{ $placement->period->start_date->format('d-m-Y') }} s.d. {{ $placement->period->end_date->format('d-m-Y') }})@endif</dd>
            <dt>Tanggal penempatan</dt>
            <dd>{{ $placement->placed_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd>
            <dt>Pembina Sekolah</dt>
            <dd>{{ $placement->schoolMentor?->user?->name ?? 'Belum ditetapkan' }}</dd>
            <dt>Pembina Industri</dt>
            <dd>{{ $placement->industryMentor?->user?->name ?? 'Belum ditetapkan' }}@if($placement->industryMentor?->phone) · {{ $placement->industryMentor->phone }}@endif</dd>
        </dl>
    </section>
@empty
    <section class="panel"><x-empty title="Belum ada penempatan" description="Koordinator PKL belum menugaskan siswa kepada Anda."/></section>
@endforelse
@endsection
