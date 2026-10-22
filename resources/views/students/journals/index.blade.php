@extends('layouts.portal')

@php($editing = $journal !== null)

@section('title', 'Jurnal Harian')

@section('content')
<x-page-heading title="Jurnal Harian" description="Simpan draf atau kirim kegiatan untuk ditinjau Pembina."/>
@if(!$period)
    <section class="panel"><x-empty title="Jurnal belum tersedia" description="Jurnal dapat dibuat setelah Koordinator menetapkan penempatan dan periode aktif."/></section>
@else
    <section class="panel form-width"><div class="section-heading"><h2>{{ $editing ? 'Perbarui jurnal' : 'Tulis jurnal' }}</h2>@if($editing)<x-badge :status="$journal->status"/>@endif</div>
        @if($editing && $journal->mentor_note)<x-alert><strong>Catatan Pembina</strong><p>{{ $journal->mentor_note }}</p></x-alert>@endif
        <form method="post" action="{{ $editing ? route('student.journals.update', $journal) : route('student.journals.store') }}" enctype="multipart/form-data">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="form-grid">
                <x-field name="activity_date" label="Tanggal kegiatan" type="date" :value="$journal?->activity_date?->format('Y-m-d') ?? now()->toDateString()" :min="$period->start_date->format('Y-m-d')" :max="$period->end_date->format('Y-m-d')" required/>
                <x-field name="title" label="Ringkasan kegiatan" :value="$journal?->title ?? ''" required maxlength="120"/>
                <x-field name="activity" label="Uraian kegiatan dan pembelajaran" type="textarea" :value="$journal?->activity ?? ''" required maxlength="10000" hint="Jelaskan pekerjaan, hasil, dan pembelajaran yang kamu dapatkan."/>
                <x-field name="documentation_url" label="URL dokumentasi (opsional)" type="url" :value="$journal?->documentation_url ?? ''" maxlength="255"/>
                <x-field name="documentation_file" label="Foto dokumentasi (opsional)" type="file" accept="image/jpeg,image/png,image/webp" hint="Format foto: JPG, PNG, atau WEBP. Maksimal 2MB."/>
                @if($editing && $journal->documentation_path)
                    <div style="grid-column: 1 / -1; margin-top: -10px; margin-bottom: 12px; font-size: 13px;">
                        <span class="muted">Foto terunggah saat ini:</span>
                        <a href="{{ asset('storage/' . $journal->documentation_path) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; color: var(--primary); font-weight: 500; margin-left: 6px;">
                            <x-icon name="file"/> Lihat foto dokumentasi
                        </a>
                    </div>
                @endif
            </div>
            <div class="actions">
                <x-button type="submit" name="action" value="send">{{ $editing ? 'Kirim Jurnal' : 'Kirim Jurnal' }}</x-button>
                <x-button type="submit" name="action" value="draft" variant="secondary">Simpan Draf</x-button>
                @if($editing)
                    <x-button variant="secondary" :href="route('student.journals.index')">Batal</x-button>
                @endif
            </div>
        </form>
    </section>
@endif
<section class="panel table-panel"><h2>Jurnal saya</h2>
    <form class="toolbar" method="get" action="{{ route('student.journals.index') }}"><div class="field search-field"><label for="journal-search">Cari jurnal</label><input type="search" id="journal-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Judul atau isi jurnal"></div><div class="field"><label for="status-filter">Status</label><select id="status-filter" name="status"><option value="">Semua status</option>@foreach(['Draf', 'Menunggu', 'Disetujui', 'Perlu Revisi'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div><x-button type="submit" variant="secondary">Terapkan</x-button><a class="reset-link" href="{{ route('student.journals.index') }}">Hapus filter</a></form>
    @if($journals->isNotEmpty())<div class="table-scroll"><table class="responsive-table"><caption class="sr-only">Daftar jurnal siswa</caption><thead><tr><th scope="col">Tanggal</th><th scope="col">Judul</th><th scope="col">Status</th><th scope="col">Catatan Pembina</th><th scope="col">Tindakan</th></tr></thead><tbody>@foreach($journals as $entry)<tr><td data-label="Tanggal">{{ $entry->activity_date->format('d-m-Y') }}</td><td data-label="Judul"><div><strong>{{ $entry->title }}</strong></div>@if($entry->documentation_url || $entry->documentation_path)<div style="display: inline-flex; gap: 8px; font-size: 12px; margin-top: 4px;">@if($entry->documentation_url)<a href="{{ $entry->documentation_url }}" target="_blank" rel="noopener noreferrer" class="muted" style="display:inline-flex; align-items:center; gap:3px;"><x-icon name="back" style="transform: rotate(135deg); width: 14px; height: 14px;"/> Tautan</a>@endif @if($entry->documentation_path)<a href="{{ asset('storage/' . $entry->documentation_path) }}" target="_blank" rel="noopener noreferrer" class="muted" style="display:inline-flex; align-items:center; gap:3px;"><x-icon name="file" style="width: 14px; height: 14px;"/> File</a>@endif</div>@endif</td><td data-label="Status"><x-badge :status="$entry->status"/></td><td data-label="Catatan Pembina">{{ $entry->mentor_note ?: 'Belum ada catatan' }}</td><td data-label="Tindakan">@if(in_array($entry->status, ['Draf', 'Perlu Revisi'], true))<div class="row-actions"><x-action :href="route('student.journals.index', ['edit' => $entry->id])" icon="edit">Ubah</x-action>@if($entry->status === 'Draf')<form method="post" action="{{ route('student.journals.destroy', $entry) }}" onsubmit="return confirm('Apakah kamu yakin ingin menghapus draf jurnal ini?')">@csrf @method('DELETE')<x-action variant="danger" icon="trash">Hapus</x-action></form>@endif</div>@else<span class="muted">Terkirim</span>@endif</td></tr>@endforeach</tbody></table></div><div class="pagination"><span>{{ $journals->total() }} jurnal</span>{{ $journals->links() }}</div>
    @else<x-empty title="Belum ada jurnal" description="Jurnal yang kamu simpan atau kirim akan tampil di sini."/>@endif
</section>
@endsection