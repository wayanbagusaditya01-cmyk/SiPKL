@extends('layouts.portal')

@section('title', 'Nilai & Catatan')

@section('content')
<x-page-heading title="Nilai & Catatan" description="Hasil yang sudah diterbitkan oleh Pembina Sekolah dan Pembina Industri."/>
@if($grades->isEmpty())
    <div class="detail-layout">
        <section class="panel"><x-empty title="Nilai belum diterbitkan" description="Nilai dan catatan akan tampil setelah Pembina menerbitkannya."/></section>
        @if($student->placement)
            <aside class="detail-aside">
                <x-person-card label="Penilai: Pembina Sekolah" :mentor="$student->placement->schoolMentor" compact/>
                <x-person-card label="Penilai: Pembina Industri" :mentor="$student->placement->industryMentor" compact/>
            </aside>
        @endif
    </div>
@else
    @php($average = (float) $averageScore)
    <div class="grade-layout">
        <section class="panel grade-summary">
            <div class="grade-ring" style="--score: {{ $average }}" role="img" aria-label="Rata-rata nilai {{ number_format($average, 2) }} dari 100">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle class="grade-ring__track" cx="60" cy="60" r="52"/>
                    <circle class="grade-ring__value" cx="60" cy="60" r="52"/>
                </svg>
                <div><strong>{{ number_format($average, 2) }}</strong><span>/ 100</span></div>
            </div>
            <div>
                <p class="eyebrow">{{ $grades->count() > 1 ? 'Rata-rata nilai PKL' : 'Nilai PKL' }}</p>
                <h2>{{ $grades->count() }} penilaian diterbitkan</h2>
                <p class="muted">Diterbitkan terakhir {{ $grades->max('published_at')?->translatedFormat('j F Y') ?? '-' }}.</p>
            </div>
        </section>

        <div class="grade-list">
            @foreach($grades as $grade)
                @php($assessor = $grade->mentor?->user?->name ?? 'Pembina PKL')
                <section class="panel grade-card">
                    <header class="grade-card__head">
                        <span class="person-card__avatar" aria-hidden="true">{{ collect(explode(' ', $assessor))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}</span>
                        <div>
                            <p class="eyebrow">{{ $grade->mentor?->typeLabel() ?? 'Penilaian PKL' }}</p>
                            <h2>{{ $assessor }}</h2>
                        </div>
                        <div class="grade-card__score"><strong>{{ number_format((float) $grade->score, 2) }}</strong><span>/ 100</span></div>
                    </header>
                    <div class="grade-card__meter"><span style="width: {{ min(100, (float) $grade->score) }}%"></span></div>
                    <dl>
                        <dt>Catatan Pembina</dt><dd class="preserve-lines">{{ $grade->feedback ?: 'Belum ada catatan.' }}</dd>
                        <dt>Tanggal penilaian</dt><dd>{{ $grade->graded_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd>
                        <dt>Penilai</dt><dd>{{ $assessor }}</dd>
                    </dl>
                    <x-badge status="Terbit"/>
                </section>
            @endforeach
        </div>
    </div>
@endif
@endsection
