@props(['period'])
@php
    $today = now()->startOfDay();
    $totalDays = max(1, (int) $period->start_date->diffInDays($period->end_date) + 1);
    $elapsedDays = $today->lt($period->start_date) ? 0 : min($totalDays, (int) $period->start_date->diffInDays($today) + 1);
    $percent = (int) round($elapsedDays / $totalDays * 100);
    $remaining = $totalDays - $elapsedDays;
@endphp
<section {{ $attributes->class('panel period-card') }}>
    <div class="period-card__head">
        <div>
            <p class="eyebrow">Periode PKL</p>
            <h2>{{ $period->name }}</h2>
        </div>
        <x-badge :status="$period->status"/>
    </div>
    <div class="period-card__meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="Progres periode PKL">
        <span style="width: {{ $percent }}%"></span>
    </div>
    <div class="period-card__numbers">
        <span><strong>{{ $percent }}%</strong> berjalan</span>
        <span>{{ $remaining > 0 ? $remaining.' hari lagi' : 'Periode selesai' }}</span>
    </div>
    <dl class="period-card__dates">
        <div><dt>Mulai</dt><dd>{{ $period->start_date->translatedFormat('j M Y') }}</dd></div>
        <div><dt>Selesai</dt><dd>{{ $period->end_date->translatedFormat('j M Y') }}</dd></div>
        <div><dt>Tahun ajaran</dt><dd>{{ $period->academic_year }}</dd></div>
    </dl>
</section>
