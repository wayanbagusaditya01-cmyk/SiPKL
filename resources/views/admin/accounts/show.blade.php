@extends('layouts.portal')

@section('title', 'Detail Akun: '.$account->name)

@section('content')
<x-page-heading :title="'Detail Akun: '.$account->name" description="Informasi akun pengguna, profil terhubung, dan pengaturan kredensial.">
    <div style="display: flex; gap: 8px;">
        <x-button variant="secondary" :href="route('admin.accounts.edit', $account)">Ubah Akun</x-button>
        <x-button variant="secondary" :href="route('admin.accounts.index')">Kembali</x-button>
    </div>
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

<div class="content-grid">
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel detail-panel">
            <div class="section-heading">
                <h2>Identitas Akun</h2>
                <x-badge :status="$account->status ? 'Aktif' : 'Nonaktif'"/>
            </div>

            <dl>
                <dt>Nama Lengkap</dt>
                <dd>{{ $account->name }}</dd>
                <dt>Email (Username)</dt>
                <dd>{{ $account->email }}</dd>
                <dt>Nomor Telepon</dt>
                <dd>{{ $account->phone ?: 'Belum diisi' }}</dd>
                <dt>Peran</dt>
                <dd>{{ $roles[$account->role]['name'] ?? $account->role }}</dd>
                <dt>Tanggal Terdaftar</dt>
                <dd>{{ $account->created_at?->format('d F Y, H:i') ?? '-' }}</dd>
            </dl>

            @if(auth()->id() !== $account->id)
                <div class="actions" style="margin-top: 16px; border-top: 1px solid var(--border, #e2e8f0); padding-top: 16px;">
                    <form method="POST" action="{{ route('admin.accounts.toggle-status', $account) }}" style="margin: 0;">
                        @csrf
                        <x-button type="submit" :variant="$account->status ? 'secondary' : 'primary'">
                            {{ $account->status ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                        </x-button>
                    </form>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Profil Terhubung</h2>
            </div>

            @if($account->role === 'siswa')
                @if($account->student)
                    <dl>
                        <dt>NIS</dt>
                        <dd>{{ $account->student->nis }}</dd>
                        <dt>Kelas</dt>
                        <dd>{{ $account->student->class }}</dd>
                        <dt>Jurusan</dt>
                        <dd>{{ $account->student->major }}</dd>
                        <dt>Tempat PKL</dt>
                        <dd>{{ $account->student->placement?->place?->name ?? 'Belum ditempatkan' }}</dd>
                    </dl>
                @else
                    <x-empty title="Profil Siswa Belum Terhubung" description="Akun ini belum memiliki catatan di data siswa."/>
                @endif
            @elseif(in_array($account->role, ['pembina', 'pembina_sekolah', 'pembina_industri']))
                @if($account->mentor)
                    <dl>
                        <dt>Tipe Pembina</dt>
                        <dd>{{ $account->mentor->type }}</dd>
                        <dt>NIP</dt>
                        <dd>{{ $account->mentor->nip ?: 'Tidak berlaku' }}</dd>
                        <dt>Jabatan</dt>
                        <dd>{{ $account->mentor->position ?: '-' }}</dd>
                        <dt>Tempat PKL Bimbingan</dt>
                        <dd>{{ $account->mentor->place?->name ?? 'Semua / Sekolah' }}</dd>
                    </dl>
                @else
                    <x-empty title="Profil Pembina Belum Terhubung" description="Koordinator PKL perlu membuat profil pembina yang terhubung ke akun ini."/>
                @endif
            @else
                <p style="color: var(--muted, #64748b);">Akun peran {{ $roles[$account->role]['name'] ?? $account->role }} dikelola langsung pada modul sistem.</p>
            @endif
        </section>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section class="panel">
            <div class="section-heading">
                <h2>Reset Kata Sandi</h2>
                <p style="font-size: 13px; color: var(--muted, #64748b); margin: 0;">Atur ulang kata sandi pengguna secara langsung.</p>
            </div>

            <form method="POST" action="{{ route('admin.accounts.reset-password', $account) }}">
                @csrf
                <div class="field" style="margin-bottom: 12px;">
                    <label for="password">Kata sandi baru (min. 12 karakter)</label>
                    <input type="password" id="password" name="password" required minlength="12" placeholder="Masukkan kata sandi baru">
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="12" placeholder="Ulangi kata sandi baru">
                </div>

                <x-button type="submit">Reset Kata Sandi</x-button>
            </form>
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Riwayat Aktivitas Akun</h2>
            </div>

            @forelse($account->activityLogs as $log)
                <div class="review-row" style="padding: 10px 0; border-bottom: 1px solid var(--border, #e2e8f0);">
                    <div>
                        <strong>{{ $log->action }}</strong>
                        <p style="margin: 2px 0 0 0; font-size: 13px;">{{ $log->detail ?: $log->entity }}</p>
                        <small style="color: var(--muted, #64748b);">{{ $log->created_at->format('d M Y, H:i') }}</small>
                    </div>
                </div>
            @empty
                <p style="font-size: 13px; color: var(--muted, #64748b);">Belum ada catatan aktivitas untuk akun ini.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
