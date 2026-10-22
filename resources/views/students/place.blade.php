@extends('layouts.portal')

@section('title', 'Detail Tempat PKL')

@section('content')
<x-page-heading title="Detail Tempat PKL" description="Informasi tempat sesuai penempatan yang tersimpan."/>
@if(!$student->placement)
    <section class="panel"><x-empty title="Tempat PKL belum tersedia" description="Detail tempat muncul setelah Koordinator menetapkan penempatan."/></section>
@else
    @php
        $placement = $student->placement;
        $place = $placement->place;
        $hasCoordinates = $place->hasCoordinates();
        $phone = $place->contact ? preg_replace('/[^0-9+]/', '', $place->contact) : null;
    @endphp
    <div class="detail-layout">
        <div class="detail-main">
            <section class="panel place-card">
                <header class="place-card__head">
                    <span class="place-card__mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($place->name, 0, 1)) }}</span>
                    <div>
                        <p class="eyebrow">Tempat PKL</p>
                        <h2>{{ $place->name }}</h2>
                        <p class="place-card__address"><x-icon name="pin"/>{{ $place->address ?: 'Alamat belum dicatat' }}</p>
                    </div>
                    <x-badge :status="$placement->status"/>
                </header>
                <dl class="info-grid">
                    <x-info-item icon="phone" label="Kontak">{{ $place->contact ?: 'Belum dicatat' }}</x-info-item>
                    <x-info-item icon="mail" label="Email">{{ $place->email ?: 'Belum dicatat' }}</x-info-item>
                    <x-info-item icon="calendar" label="Hari kerja">{{ $place->workDaysLabel() }}</x-info-item>
                    <x-info-item icon="clock" label="Jam kerja">{{ $place->scheduleTime('check_in_end') }} – {{ $place->scheduleTime('check_out_start') }}</x-info-item>
                    <x-info-item icon="pin" label="Koordinat">{{ $hasCoordinates ? $place->latitude.', '.$place->longitude : 'Belum dicatat' }}</x-info-item>
                    <x-info-item icon="shield" label="Radius absensi">{{ $place->attendance_radius }} meter</x-info-item>
                </dl>
            </section>

            <section class="panel map-card">
                <div class="section-heading">
                    <div>
                        <h2>Lokasi</h2>
                        <p>Titik yang dipakai untuk absensi berbasis lokasi.</p>
                    </div>
                    @if($hasCoordinates)
                        <a class="button secondary" href="https://www.google.com/maps?q={{ $place->latitude }},{{ $place->longitude }}" target="_blank" rel="noopener noreferrer"><x-icon name="external"/>Buka di Google Maps</a>
                    @endif
                </div>
                @if($hasCoordinates)
                    @php($lat = (float) $place->latitude)
                    @php($lng = (float) $place->longitude)
                    <iframe class="map-frame" title="Peta lokasi {{ $place->name }}" loading="lazy" referrerpolicy="no-referrer"
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lng - 0.006 }},{{ $lat - 0.004 }},{{ $lng + 0.006 }},{{ $lat + 0.004 }}&amp;layer=mapnik&amp;marker={{ $lat }},{{ $lng }}"></iframe>
                @else
                    <x-empty title="Titik lokasi belum diatur" description="Pembina Industri dapat mengatur titik lokasi melalui menu Jadwal & Lokasi."/>
                @endif
            </section>
        </div>

        <aside class="detail-aside">
            <section class="panel">
                <p class="eyebrow">Kontak cepat</p>
                <div class="quick-actions">
                    @if($phone)
                        <a class="quick-action" href="tel:{{ $phone }}"><span><x-icon name="phone"/></span><div><strong>Telepon</strong><small>{{ $place->contact }}</small></div></a>
                    @endif
                    @if($place->email)
                        <a class="quick-action" href="mailto:{{ $place->email }}"><span><x-icon name="mail"/></span><div><strong>Kirim email</strong><small>{{ $place->email }}</small></div></a>
                    @endif
                    <a class="quick-action" href="{{ route('student.attendance.index') }}"><span><x-icon name="calendar"/></span><div><strong>Absensi</strong><small>Absen masuk dan pulang</small></div></a>
                </div>
            </section>
            <x-person-card label="Pembina Industri" :mentor="$placement->industryMentor" compact/>
            @if($placement->period)
                <x-period-progress :period="$placement->period"/>
            @endif
        </aside>
    </div>
@endif
@endsection
