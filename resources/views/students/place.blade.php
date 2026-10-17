@extends('layouts.portal')

@section('title', 'Detail Tempat PKL')

@section('content')
<x-page-heading title="Detail Tempat PKL" description="Informasi tempat sesuai penempatan yang tersimpan."/>
@if(!$student->placement)
    <section class="panel"><x-empty title="Tempat PKL belum tersedia" description="Detail tempat muncul setelah Koordinator menetapkan penempatan."/></section>
@else
    @php($place = $student->placement->place)
    <section class="panel form-width"><div class="section-heading"><h2>{{ $place->name }}</h2><span class="round-icon"><x-icon name="place"/></span></div><dl><dt>Alamat</dt><dd>{{ $place->address ?: 'Belum dicatat' }}</dd><dt>Kontak</dt><dd>{{ $place->contact ?: 'Belum dicatat' }}</dd><dt>Email</dt><dd>{{ $place->email ?: 'Belum dicatat' }}</dd><dt>Koordinat</dt><dd>{{ $place->latitude && $place->longitude ? $place->latitude.', '.$place->longitude : 'Belum dicatat' }}</dd></dl></section>
@endif
@endsection