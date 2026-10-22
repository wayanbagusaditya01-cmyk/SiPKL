@extends('layouts.portal')

@section('title', 'Absensi')

@push('scripts')
    @vite('resources/js/attendance.js')
@endpush

@section('content')
@php
    $holiday = $state === 'holiday' ? $place->holidayOn(now()) : null;
    [$pill, $tone, $buttonLabel, $hint] = match ($state) {
        'check-in' => ['Mencari lokasi', 'neutral', 'Masuk', ''],
        'check-out' => ['Mencari lokasi', 'neutral', 'Pulang', ''],
        'check-in-wait' => ['Dibuka '.$place->scheduleTime('check_in_start'), 'neutral', 'Masuk', 'Absen masuk belum dibuka.'],
        'done' => ['Selesai', 'ok', 'Selesai', ''],
        'leave' => ['Izin', 'warn', 'Izin', ''],
        'holiday' => ['Libur', 'warn', 'Libur', $holiday?->name ?? ''],
        'off-day' => ['Libur', 'warn', 'Libur', 'Hari ini bukan hari kerja.'],
        'missed' => ['Ditutup', 'warn', 'Ditutup', 'Waktu absen masuk sudah lewat.'],
        'no-location' => ['Lokasi belum diatur', 'warn', 'Masuk', 'Pembina Industri belum mengatur titik lokasi.'],
        default => ['Belum tersedia', 'neutral', 'Masuk', 'Menunggu penempatan dan periode PKL.'],
    };
    $canUseLocation = in_array($state, ['check-in', 'check-out'], true);
    $isFinished = in_array($state, ['done', 'leave'], true);
    $hasLeaveErrors = $errors->hasAny(['attendance_date', 'note', 'leave_photo']);
@endphp

<div class="attend" data-attendance data-state="{{ $state }}" data-timezone="{{ config('app.timezone') }}" data-csrf="{{ csrf_token() }}"
    data-max-accuracy="{{ \App\Http\Controllers\Student\AttendanceController::MAX_GPS_ACCURACY }}"
    @if($opensAt) data-opens-at="{{ $opensAt->toIso8601String() }}" @endif
    @if($canUseLocation)
        data-place-lat="{{ $place->latitude }}" data-place-lng="{{ $place->longitude }}" data-radius="{{ $place->attendance_radius }}"
        data-url="{{ $state === 'check-in' ? route('student.attendance.check-in') : route('student.attendance.check-out') }}"
        @if($state === 'check-out') data-leave-at="{{ $place->scheduleAt(now(), 'check_out_start')->toIso8601String() }}" data-leave-time="{{ $place->scheduleTime('check_out_start') }}" @endif
    @endif>
    <h1 class="sr-only">Absensi</h1>

    <section class="attend-main" aria-label="Absensi hari ini">
        <header class="attend-main__head">
            <span class="attend-place">{{ $place?->name ?? 'Belum ditempatkan' }}</span>
            <span class="attend-pill attend-pill--{{ $tone }}" data-status>{{ $pill }}</span>
        </header>

        <p class="attend-clock" data-clock>{{ now()->format('H:i') }}<span>:{{ now()->format('s') }}</span></p>
        <p class="attend-date">{{ now()->translatedFormat('l, j F Y') }}</p>

        <div class="attend-dial">
            <svg viewBox="0 0 300 300" aria-hidden="true">
                <defs>
                    <linearGradient id="attend-sweep" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#8fb0e3" stop-opacity="0"/>
                        <stop offset="100%" stop-color="#8fb0e3" stop-opacity=".35"/>
                    </linearGradient>
                </defs>
                <g class="attend-dial__rings" data-rings>
                    <circle cx="150" cy="150" r="140"/>
                    <circle cx="150" cy="150" r="120"/>
                </g>
                <path class="attend-dial__sweep" data-sweep d="M150 150 L150 10 A140 140 0 0 1 249 51 Z" fill="url(#attend-sweep)"/>
                <circle class="attend-dial__fence" data-fence cx="150" cy="150" r="100"/>
                <circle class="attend-dial__pulse" data-pulse cx="150" cy="150" r="64"/>
                <g class="attend-dial__me" data-me opacity="0">
                    <circle cx="0" cy="0" r="8"/>
                </g>
            </svg>
            <button type="button" class="attend-button" data-attend-button disabled>
                <span class="attend-button__ring" aria-hidden="true"></span>
                <x-icon :name="$isFinished ? 'check' : 'place'"/>
                <span data-button-label>{{ $buttonLabel }}</span>
            </button>
        </div>

        <p class="attend-hint" data-hint aria-live="polite">{{ $hint }}</p>
        @if($canUseLocation)
            <button type="button" class="attend-retry" data-refresh hidden>Coba lagi</button>
        @endif

        <dl class="attend-times">
            <div>
                <dt>Masuk</dt>
                <dd>{{ $todayAttendance?->time_in ?? '--:--' }}</dd>
                @if($todayAttendance?->late_minutes)<small class="attend-late">+{{ $todayAttendance->late_minutes }} mnt</small>@endif
            </div>
            <div>
                <dt>Pulang</dt>
                <dd>{{ $todayAttendance?->time_out ?? '--:--' }}</dd>
            </div>
            <div>
                <dt>Jadwal</dt>
                <dd>{{ $place ? $place->scheduleTime('check_in_end').'–'.$place->scheduleTime('check_out_start') : '--' }}</dd>
            </div>
        </dl>

        <div class="attend-success" data-success hidden>
            <div class="attend-success__badge">
                <span class="attend-success__halo" aria-hidden="true"></span>
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle class="attend-success__circle" cx="60" cy="60" r="50"/>
                    <path class="attend-success__check" d="M38 62 L53 77 L84 45"/>
                </svg>
            </div>
            <p class="attend-success__time" data-success-time aria-hidden="true"></p>
            <p class="attend-success__title" data-success-title></p>
            <p class="attend-success__note" data-success-note></p>
            <p class="sr-only" data-success-message role="status"></p>
        </div>
    </section>

    <div class="attend-side">
        <dl class="attend-stats">
            <div class="attend-stat"><dt>Hadir</dt><dd data-count="{{ $summary['present'] }}">{{ $summary['present'] }}</dd></div>
            <div class="attend-stat"><dt>Izin</dt><dd data-count="{{ $summary['leave'] }}">{{ $summary['leave'] }}</dd></div>
            <div class="attend-stat"><dt>Kehadiran</dt><dd><span data-count="{{ $summary['rate'] }}">{{ $summary['rate'] }}</span>%</dd></div>
        </dl>

        <section class="attend-history" aria-labelledby="history-title">
            <header class="attend-history__head">
                <h2 id="history-title">Riwayat</h2>
                @if($period)
                    <x-button variant="secondary" data-dialog="leave-dialog"><x-icon name="plus"/>Ajukan izin</x-button>
                @endif
            </header>

            @if($attendances->isNotEmpty())
                <ul class="attend-list">
                    @foreach($attendances as $attendance)
                        <li class="attend-row" data-animate="entry">
                            <span class="attend-row__date attend-row__date--{{ $attendance->status === 'Izin' ? 'leave' : 'present' }}">
                                <strong>{{ $attendance->attendance_date->format('d') }}</strong>{{ $attendance->attendance_date->translatedFormat('M') }}
                            </span>
                            <div class="attend-row__body">
                                <span class="attend-row__day">{{ $attendance->attendance_date->translatedFormat('l') }}</span>
                                @if($attendance->status === 'Izin')
                                    <span class="attend-row__note">{{ $attendance->note }}</span>
                                @else
                                    <span class="attend-row__times">{{ $attendance->time_in ?? '--:--' }} – {{ $attendance->time_out ?? '--:--' }}</span>
                                @endif
                            </div>
                            <div class="attend-row__tags">
                                @if($attendance->late_minutes)<span class="badge attend-badge-late">Terlambat</span>@endif
                                @if($attendance->early_leave_minutes)<span class="badge attend-badge-late">Pulang cepat</span>@endif
                                @if($attendance->leave_photo_path)
                                    <a class="attend-row__photo" href="{{ asset('storage/'.$attendance->leave_photo_path) }}" target="_blank" rel="noopener noreferrer"><img src="{{ asset('storage/'.$attendance->leave_photo_path) }}" alt="Foto bukti izin {{ $attendance->attendance_date->format('d-m-Y') }}" loading="lazy"></a>
                                @endif
                                <x-badge :status="$attendance->status"/>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="pagination">{{ $attendances->links() }}</div>
            @else
                <x-empty title="Belum ada absensi" description="Riwayat absensi akan tampil di sini."/>
            @endif
        </section>
    </div>

    @if($period)
        <dialog id="leave-dialog" class="attend-dialog" aria-labelledby="leave-title" @if($hasLeaveErrors) data-open-on-load @endif>
            <div class="dialog-heading">
                <h2 id="leave-title">Ajukan izin</h2>
                <button type="button" class="icon-button" data-close-dialog aria-label="Tutup"><x-icon name="close"/></button>
            </div>
            <form method="post" action="{{ route('student.attendance.leave') }}" enctype="multipart/form-data" class="attend-leave">
                @csrf
                <x-field name="attendance_date" label="Tanggal" type="date" :value="old('attendance_date', now()->toDateString())" :min="$period->start_date->format('Y-m-d')" :max="min(now(), $period->end_date)->format('Y-m-d')" required/>
                <x-field name="note" label="Alasan" type="textarea" :value="old('note')" maxlength="1000" required/>
                <div class="attend-photo" data-photo-picker>
                    <input type="file" id="leave-photo" name="leave_photo" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input>
                    <img alt="" data-photo-preview hidden>
                    <div class="attend-photo__actions">
                        <label for="leave-photo" class="button secondary attend-photo__add"><x-icon name="plus"/><span data-photo-label>Tambah foto</span></label>
                        <button type="button" class="attend-photo__remove" data-photo-remove hidden>Hapus</button>
                    </div>
                    <small>Opsional · JPG, PNG, WEBP maks. 2 MB</small>
                    @error('leave_photo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="actions">
                    <x-button variant="secondary" data-close-dialog>Batal</x-button>
                    <x-button type="submit">Kirim izin</x-button>
                </div>
            </form>
        </dialog>
    @endif
</div>
@endsection
