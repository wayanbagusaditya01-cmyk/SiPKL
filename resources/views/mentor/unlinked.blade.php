@extends('layouts.portal')

@section('title', 'Ringkasan')

@section('content')
<x-page-heading title="Profil Pembina Belum Terhubung" description="Akun Anda sudah aktif, tetapi belum dihubungkan ke data Pembina."/>

<section class="panel form-width">
    <x-empty title="Menunggu Koordinator PKL" description="Koordinator PKL perlu menambahkan profil Pembina untuk akun ini melalui menu Data Pembina, lalu menugaskan siswa melalui menu Penempatan."/>
    <dl>
        <dt>Nama akun</dt>
        <dd>{{ $user->name }}</dd>
        <dt>Email login</dt>
        <dd>{{ $user->email }}</dd>
        <dt>Peran</dt>
        <dd>{{ $user->role === 'pembina_industri' ? 'Pembina Industri' : ($user->role === 'pembina_sekolah' ? 'Pembina Sekolah' : 'Pembina PKL') }}</dd>
    </dl>
</section>
@endsection
