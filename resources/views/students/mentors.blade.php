@extends('layouts.portal')

@section('title', 'Pembina Saya')

@section('content')
<x-page-heading title="Pembina Saya" description="Pembina yang terhubung ke penempatan PKL-mu."/>
@if(!$student->placement)
    <section class="panel"><x-empty title="Pembina belum tersedia" description="Pembina akan muncul setelah penempatan ditetapkan."/></section>
@else
    <div class="mentor-grid">
        <x-person-card label="Pembina Sekolah" :mentor="$student->placement->schoolMentor"/>
        <x-person-card label="Pembina Industri" :mentor="$student->placement->industryMentor"/>
    </div>
    <section class="panel role-guide">
        <h2>Peran pembina selama PKL</h2>
        <div class="role-guide__grid">
            <div>
                <span class="info-item__icon" aria-hidden="true"><x-icon name="book"/></span>
                <div><strong>Pembina Sekolah</strong><p>Guru pembimbing dari sekolah yang memantau absensi, meninjau jurnal harian, dan memberi nilai PKL.</p></div>
            </div>
            <div>
                <span class="info-item__icon" aria-hidden="true"><x-icon name="briefcase"/></span>
                <div><strong>Pembina Industri</strong><p>Pembimbing di {{ $student->placement->place->name }} yang mengatur jadwal kerja dan titik lokasi absensi, meninjau jurnal, dan memberi nilai PKL.</p></div>
            </div>
        </div>
    </section>
@endif
@endsection
