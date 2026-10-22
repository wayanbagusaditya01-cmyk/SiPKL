@extends('layouts.portal')

@section('title', 'Penempatan Saya')

@section('content')
<x-page-heading title="Penempatan Saya" description="Tempat dan periode PKL yang ditetapkan Koordinator."/>
@if(!$student->placement)
    <section class="panel"><x-empty title="Penempatan belum ditetapkan" description="Informasi penempatan akan muncul setelah Koordinator mengaturnya."/></section>
@else
    @php
        $placement = $student->placement;
        $period = $placement->period;
        $today = now()->startOfDay();
        $milestones = collect([
            ['label' => 'Ditempatkan', 'date' => $placement->placed_at, 'note' => 'Penempatan ditetapkan Koordinator'],
            ['label' => 'Mulai PKL', 'date' => $period?->start_date, 'note' => 'Hari pertama di tempat PKL'],
            ['label' => 'Selesai PKL', 'date' => $period?->end_date, 'note' => 'Akhir periode dan penilaian'],
        ])->filter(fn ($milestone) => $milestone['date'] !== null);
    @endphp
    <div class="detail-layout">
        <div class="detail-main">
            <section class="panel place-card">
                <header class="place-card__head">
                    <span class="place-card__mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($placement->place->name, 0, 1)) }}</span>
                    <div>
                        <p class="eyebrow">Tempat PKL</p>
                        <h2>{{ $placement->place->name }}</h2>
                        <p class="place-card__address"><x-icon name="pin"/>{{ $placement->place->address ?: 'Alamat belum dicatat' }}</p>
                    </div>
                    <x-badge :status="$placement->status"/>
                </header>
                <dl class="info-grid">
                    <x-info-item icon="calendar" label="Periode">{{ $period?->name ?? 'Belum diatur' }}</x-info-item>
                    <x-info-item icon="book" label="Tahun ajaran">{{ $period?->academic_year ?? 'Belum diatur' }}</x-info-item>
                    <x-info-item icon="briefcase" label="Tanggal penempatan">{{ $placement->placed_at?->format('d-m-Y') ?? 'Belum dicatat' }}</x-info-item>
                    <x-info-item icon="place" label="Tempat">{{ $placement->place->name }}</x-info-item>
                </dl>
                <div class="actions"><x-button :href="route('student.portal.place')">Detail tempat PKL</x-button><x-button variant="secondary" :href="route('student.portal.mentors')">Pembina saya</x-button></div>
            </section>

            @if($milestones->isNotEmpty())
                <section class="panel">
                    <h2>Perjalanan PKL</h2>
                    <ol class="timeline">
                        @foreach($milestones as $milestone)
                            <li class="timeline__item {{ $milestone['date']->lte($today) ? 'is-done' : '' }}">
                                <span class="timeline__dot" aria-hidden="true">@if($milestone['date']->lte($today))<x-icon name="check"/>@endif</span>
                                <div>
                                    <strong>{{ $milestone['label'] }}</strong>
                                    <small>{{ $milestone['date']->translatedFormat('l, j F Y') }} · {{ $milestone['note'] }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <aside class="detail-aside">
            @if($period)
                <x-period-progress :period="$period"/>
            @endif
            <x-person-card label="Pembina Sekolah" :mentor="$placement->schoolMentor" compact/>
            <x-person-card label="Pembina Industri" :mentor="$placement->industryMentor" compact/>
        </aside>
    </div>
@endif
@endsection
