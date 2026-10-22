@extends('layouts.portal')

@section('title', 'Master Data Sistem')

@section('content')
<x-page-heading title="Master Data Sistem" description="Nilai referensi dan konfigurasi data lintas modul yang tersimpan dan dikelola langsung pada database.">
    <button type="button" class="button" onclick="document.getElementById('new-reference-modal').scrollIntoView({ behavior: 'smooth' })">
        <x-icon name="plus"/>Tambah Data Referensi
    </button>
</x-page-heading>

@if(session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif
@if($errors->any())
    <x-alert type="error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

<nav class="status-tabs" aria-label="Filter kategori" style="margin-bottom: 20px;">
    <a href="{{ route('admin.master-data.index') }}" @if(!$selectedCategory) aria-current="page" @endif>Semua Kategori</a>
    @foreach($categories as $cat)
        <a href="{{ route('admin.master-data.index', ['category' => $cat]) }}" @if($selectedCategory === $cat) aria-current="page" @endif>
            {{ $cat }}
        </a>
    @endforeach
</nav>

<div class="content-grid" style="align-items: flex-start;">
    <div style="display: flex; flex-direction: column; gap: 24px;">
        @foreach($grouped as $categoryName => $items)
            <section class="panel">
                <div class="section-heading">
                    <div>
                        <h2>{{ $categoryName }}</h2>
                        <p style="margin: 0; font-size: 13px; color: var(--muted, #64748b);">Pengelola: {{ $items->first()->manager_role ?? 'Admin' }}</p>
                    </div>
                    <span style="font-size: 13px; font-weight: 600; color: var(--primary);">{{ $items->count() }} item</span>
                </div>

                <div class="table-scroll">
                    <table class="responsive-table">
                        <caption class="sr-only">Referensi {{ $categoryName }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Nama / Nilai</th>
                                <th scope="col">Deskripsi</th>
                                <th scope="col">Sifat</th>
                                <th scope="col">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td data-label="Kode">
                                        <code style="font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">{{ $item->code }}</code>
                                    </td>
                                    <td data-label="Nama / Nilai">
                                        <strong>{{ $item->name }}</strong>
                                    </td>
                                    <td data-label="Deskripsi" style="font-size: 13px; color: var(--muted, #64748b);">
                                        {{ $item->description ?: '-' }}
                                    </td>
                                    <td data-label="Sifat">
                                        @if($item->is_system)
                                            <span style="font-size: 11px; background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px;">Sistem</span>
                                        @else
                                            <span style="font-size: 11px; background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px;">Kustom</span>
                                        @endif
                                    </td>
                                    <td data-label="Tindakan">
                                        <details style="position: relative;">
                                            <summary class="row-action" style="cursor: pointer; list-style: none;">Ubah</summary>
                                            <div style="margin-top: 8px; padding: 12px; background: #f8fafc; border: 1px solid var(--border, #cbd5e1); border-radius: 8px;">
                                                <form method="POST" action="{{ route('admin.master-data.update', $item) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="field" style="margin-bottom: 8px;">
                                                        <label style="font-size: 12px;">Nama / Nilai</label>
                                                        <input type="text" name="name" value="{{ $item->name }}" required maxlength="255" style="padding: 6px 8px; font-size: 13px;">
                                                    </div>
                                                    <div class="field" style="margin-bottom: 8px;">
                                                        <label style="font-size: 12px;">Deskripsi</label>
                                                        <input type="text" name="description" value="{{ $item->description }}" maxlength="500" style="padding: 6px 8px; font-size: 13px;">
                                                    </div>
                                                    <x-button type="submit" style="padding: 6px 12px; font-size: 12px;">Simpan</x-button>
                                                </form>
                                            </div>
                                        </details>

                                        @if(!$item->is_system)
                                            <form method="POST" action="{{ route('admin.master-data.destroy', $item) }}" onsubmit="return confirm('Hapus master data {{ $item->name }}?');" style="display: inline; margin-left: 8px;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="row-action" style="background: none; border: none; cursor: pointer; color: #dc2626;">Hapus</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    <div>
        <section class="panel" id="new-reference-modal">
            <div class="section-heading">
                <h2>Tambah Data Referensi</h2>
            </div>
            <p style="font-size: 13px; color: var(--muted, #64748b); margin-top: 0;">
                Tambahkan referensi kategori kustom baru, seperti Jurusan, Kelas, atau nilai pendukung lainnya.
            </p>

            <form method="POST" action="{{ route('admin.master-data.store') }}">
                @csrf
                <div class="field" style="margin-bottom: 12px;">
                    <label for="category">Kategori</label>
                    <input list="category-options" id="category" name="category" required placeholder="Contoh: Jurusan atau Kelas" value="{{ $selectedCategory ?? old('category') }}">
                    <datalist id="category-options">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="field" style="margin-bottom: 12px;">
                    <label for="code">Kode unik (hanya huruf, angka, garis bawah/strip)</label>
                    <input type="text" id="code" name="code" required maxlength="64" placeholder="Contoh: rpl atau xii_rpl_1" value="{{ old('code') }}">
                </div>

                <div class="field" style="margin-bottom: 12px;">
                    <label for="name">Nama / Nilai referensi</label>
                    <input type="text" id="name" name="name" required maxlength="255" placeholder="Contoh: Rekayasa Perangkat Lunak" value="{{ old('name') }}">
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="description">Deskripsi (opsional)</label>
                    <textarea id="description" name="description" rows="2" maxlength="500" placeholder="Keterangan singkat nilai referensi">{{ old('description') }}</textarea>
                </div>

                <x-button type="submit">Tambah Referensi</x-button>
            </form>
        </section>
    </div>
</div>
@endsection
