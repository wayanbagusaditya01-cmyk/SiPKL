@extends('layouts.portal')

@section('title', 'Hak Akses')

@section('content')
<x-page-heading title="Hak Akses" description="Matriks hak akses empat peran yang tersimpan dan dikelola langsung pada database."/>

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

<section class="panel">
    <div style="margin-bottom: 16px;">
        <p style="margin: 0; color: var(--muted, #64748b); font-size: 14px;">
            Atur tingkat akses modul untuk masing-masing peran (Admin, Koordinator PKL, Pembina PKL, Siswa). Perubahan akan langsung tersimpan ke database.
        </p>
    </div>

    @php
        $options = [
            'Kelola' => 'Kelola',
            'Pantau' => 'Pantau',
            'Verifikasi bimbingan' => 'Verifikasi bimbingan',
            'Kelola bimbingan' => 'Kelola bimbingan',
            'Baca bimbingan' => 'Baca bimbingan',
            'Isi milik sendiri' => 'Isi milik sendiri',
            'Baca milik sendiri' => 'Baca milik sendiri',
            'Baca hasil terbit' => 'Baca hasil terbit',
            'Kelola / baca audit' => 'Kelola / baca audit',
            'Tidak tersedia' => 'Tidak tersedia',
        ];
    @endphp

    <form method="POST" action="{{ route('admin.permissions.update') }}">
        @csrf
        @method('PUT')

        <div class="table-scroll">
            <table class="responsive-table">
                <caption class="sr-only">Matriks hak akses empat peran</caption>
                <thead>
                    <tr>
                        <th scope="col" style="min-width: 180px;">Fitur / Modul</th>
                        @foreach($roles as $roleKey => $roleData)
                            <th scope="col" style="min-width: 160px;">{{ $roleData['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $moduleKey => $module)
                        <tr>
                            <td data-label="Fitur / Modul">
                                <strong>{{ $module['name'] }}</strong>
                            </td>
                            @foreach($roles as $roleKey => $roleData)
                                @php
                                    $currentLevel = $module['roles'][$roleKey] ?? 'Tidak tersedia';
                                    $isAdminAccount = $roleKey === 'admin' && in_array($moduleKey, ['accounts', 'audit']);
                                @endphp
                                <td data-label="{{ $roleData['name'] }}">
                                    @if($isAdminAccount)
                                        <input type="hidden" name="permissions[{{ $moduleKey }}][{{ $roleKey }}]" value="{{ $currentLevel }}">
                                        <span style="font-weight: 600; color: var(--primary);">{{ $currentLevel }}</span>
                                        <small style="display: block; font-size: 11px; color: var(--muted, #64748b);">Terkunci (Sistem)</small>
                                    @else
                                        <select name="permissions[{{ $moduleKey }}][{{ $roleKey }}]" style="padding: 6px 10px; font-size: 13px; border-radius: 6px; border: 1px solid var(--border, #cbd5e1); width: 100%;">
                                            @foreach($options as $val => $label)
                                                <option value="{{ $val }}" @selected($currentLevel === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="actions" style="margin-top: 24px;">
            <x-button type="submit">Simpan Hak Akses</x-button>
        </div>
    </form>

    <div style="margin-top: 20px; padding: 12px 16px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border, #e2e8f0);">
        <p class="muted" style="margin: 0; font-size: 13px;">
            <strong>Catatan Otorisasi:</strong> Hak akses Admin untuk Akun & Audit dikunci untuk menjaga integritas sistem. Modul operasional dikelola oleh Koordinator dan Pembina PKL.
        </p>
    </div>
</section>
@endsection
