@extends('layouts.portal')

@section('title', 'Jurnal')

@section('content')
<x-page-heading title="Jurnal Siswa Bimbingan" :description="'Tinjau dan verifikasi jurnal harian siswa. ' . $pendingCount . ' jurnal menunggu tinjauan.'"/>

<div class="content-grid">
    {{-- Journal list --}}
    <section class="panel table-panel">
        <h2>Daftar jurnal</h2>
        <form class="toolbar" method="get" action="{{ route('mentor.journals.index') }}">
            <div class="field search-field">
                <label for="journal-search">Cari jurnal</label>
                <input type="search" id="journal-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Judul, isi, atau nama siswa">
            </div>
            <div class="field">
                <label for="status-filter">Status</label>
                <select id="status-filter" name="status">
                    <option value="">Semua status</option>
                    @foreach(['Menunggu', 'Disetujui', 'Perlu Revisi'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="student-filter">Siswa</label>
                <select id="student-filter" name="student">
                    <option value="">Semua siswa</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(($filters['student'] ?? '') == $student->id)>{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit" variant="secondary">Terapkan</x-button>
            <a class="reset-link" href="{{ route('mentor.journals.index') }}">Hapus filter</a>
        </form>

        @if($journals->isNotEmpty())
            @foreach($journals as $journal)
                <a href="{{ route('mentor.journals.index', ['journal' => $journal->id] + request()->except('journal')) }}" class="review-row" style="text-decoration: none; color: inherit; {{ $selected && $selected->id === $journal->id ? 'background: var(--bg-hover); border-radius: 8px;' : '' }}">
                    <div>
                        <strong>{{ $journal->title }}</strong>
                        <small>{{ $journal->student->name ?? 'Siswa' }} · {{ $journal->activity_date->format('d-m-Y') }}</small>
                    </div>
                    <x-badge :status="$journal->status"/>
                </a>
            @endforeach
            <div class="pagination">
                <span>{{ $journals->total() }} jurnal</span>
                {{ $journals->links() }}
            </div>
        @else
            <x-empty title="Belum ada jurnal" description="Jurnal siswa bimbingan yang telah dikirim akan muncul di sini."/>
        @endif
    </section>

    {{-- Review panel --}}
    <section class="panel">
        <h2>Detail Jurnal</h2>
        @if($selected)
            <div style="margin-bottom: 16px;">
                <div class="section-heading">
                    <div>
                        <strong style="font-size: 1.1em;">{{ $selected->title }}</strong>
                        <p class="muted">{{ $selected->student->name ?? 'Siswa' }} · {{ $selected->activity_date->format('d-m-Y') }}</p>
                    </div>
                    <x-badge :status="$selected->status"/>
                </div>

                <div style="margin-top: 16px;">
                    <h3 style="font-size: 0.85em; color: var(--text-secondary); margin-bottom: 6px;">Uraian kegiatan</h3>
                    <p class="preserve-lines" style="line-height: 1.7;">{{ $selected->activity }}</p>
                </div>

                @if($selected->documentation_url)
                    <div style="margin-top: 12px;">
                        <h3 style="font-size: 0.85em; color: var(--text-secondary); margin-bottom: 4px;">Dokumentasi</h3>
                        <a href="{{ $selected->documentation_url }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; color: var(--primary); font-weight: 500;">
                            <x-icon name="back" style="transform: rotate(135deg); width: 14px; height: 14px;"/> Buka tautan
                        </a>
                    </div>
                @endif

                @if($selected->documentation_path)
                    <div style="margin-top: 8px;">
                        <a href="{{ asset('storage/' . $selected->documentation_path) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; color: var(--primary); font-weight: 500;">
                            <x-icon name="file" style="width: 14px; height: 14px;"/> Lihat file dokumentasi
                        </a>
                    </div>
                @endif

                @if($selected->mentor_note)
                    <div style="margin-top: 16px;">
                        <h3 style="font-size: 0.85em; color: var(--text-secondary); margin-bottom: 4px;">Catatan Pembina sebelumnya</h3>
                        <p class="preserve-lines">{{ $selected->mentor_note }}</p>
                    </div>
                @endif
            </div>

            @if($selected->status === 'Menunggu')
                <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
                <h3>Tindakan</h3>

                <form method="post" action="{{ route('mentor.journals.approve', $selected) }}" style="margin-bottom: 12px;">
                    @csrf
                    <x-field name="mentor_note" label="Catatan (opsional)" type="textarea" maxlength="3000" hint="Berikan catatan untuk siswa jika diperlukan."/>
                    <div class="actions" style="margin-top: 12px;">
                        <x-button type="submit">Setujui Jurnal</x-button>
                    </div>
                </form>

                <form method="post" action="{{ route('mentor.journals.revise', $selected) }}">
                    @csrf
                    <x-field name="mentor_note" label="Catatan revisi (wajib)" type="textarea" maxlength="3000" required hint="Jelaskan bagian yang perlu diperbaiki."/>
                    <div class="actions" style="margin-top: 12px;">
                        <x-button type="submit" variant="secondary">Minta Revisi</x-button>
                    </div>
                </form>
            @endif
        @else
            <x-empty title="Pilih jurnal" description="Klik jurnal dari daftar di samping untuk melihat detail dan melakukan tinjauan."/>
        @endif
    </section>
</div>
@endsection
