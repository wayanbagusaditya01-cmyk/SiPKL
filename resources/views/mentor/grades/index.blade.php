@extends('layouts.portal')

@section('title', 'Penilaian')

@section('content')
<x-page-heading title="Penilaian PKL" :description="'Beri nilai akhir sebagai '.$mentor->typeLabel().'. Nilai draf tidak terlihat siswa sampai diterbitkan.'"/>

<div class="content-grid">
    <section class="panel table-panel">
        <h2>Siswa yang dinilai</h2>
        <form class="toolbar" method="get" action="{{ route('mentor.grades.index') }}">
            <div class="field search-field">
                <label for="grade-search">Cari siswa</label>
                <input type="search" id="grade-search" name="q" value="{{ $q }}" maxlength="100" placeholder="Nama atau NIS siswa">
            </div>
            <x-button type="submit" variant="secondary">Terapkan</x-button>
            <a class="reset-link" href="{{ route('mentor.grades.index') }}">Hapus filter</a>
        </form>

        @forelse($placements as $placement)
            @php($grade = $placement->student?->grades->first())
            <a href="{{ route('mentor.grades.index', ['student' => $placement->student_id] + request()->only('q')) }}" class="review-row" style="text-decoration: none; color: inherit; {{ $grading && $grading->id === $placement->student_id ? 'background: var(--bg-hover); border-radius: 8px;' : '' }}">
                <span class="avatar">{{ mb_substr($placement->student?->name ?? '?', 0, 1) }}</span>
                <div>
                    <strong>{{ $placement->student?->name ?? 'Siswa' }}</strong>
                    <small>{{ $placement->student?->nis }} · {{ $placement->place?->name ?? 'Tempat belum ditetapkan' }}{{ $grade ? ' · Nilai '.number_format((float) $grade->score, 2) : '' }}</small>
                </div>
                <x-badge :status="$grade?->status ?? 'Belum dinilai'"/>
            </a>
        @empty
            <x-empty title="Belum ada siswa" description="Siswa bimbingan akan muncul di sini setelah ditempatkan oleh Koordinator PKL."/>
        @endforelse
    </section>

    <section class="panel">
        <h2>Form Penilaian</h2>
        @if($grading)
            <div class="section-heading">
                <div>
                    <strong>{{ $grading->name }}</strong>
                    <p class="muted">{{ $grading->nis }} · {{ $grading->class }}</p>
                </div>
                <x-badge :status="$currentGrade?->status ?? 'Belum dinilai'"/>
            </div>
            <form method="post" action="{{ route('mentor.grades.store') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $grading->id }}">
                <div class="form-grid">
                    <x-field name="score" label="Nilai akhir (0–100)" type="number" :value="$currentGrade?->score ?? ''" required min="0" max="100" step="0.01"/>
                    <x-field name="feedback" label="Catatan untuk siswa" type="textarea" :value="$currentGrade?->feedback ?? ''" maxlength="3000"/>
                </div>
                <div class="actions">
                    <x-button type="submit" name="action" value="publish">Terbitkan Nilai</x-button>
                    <x-button type="submit" name="action" value="draft" variant="secondary">Simpan Draf</x-button>
                </div>
            </form>
            @if($currentGrade?->status === 'Terbit')
                <p class="muted" style="margin-top: 12px;">Diterbitkan {{ $currentGrade->published_at?->format('d-m-Y H:i') }}. Menyimpan draf akan menyembunyikan nilai dari siswa.</p>
            @endif
        @else
            <x-empty title="Pilih siswa" description="Klik nama siswa di samping untuk mengisi atau mengubah nilai."/>
        @endif
    </section>
</div>
@endsection
