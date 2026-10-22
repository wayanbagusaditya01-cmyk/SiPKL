@extends('layouts.portal')

@section('title', 'Jadwal & Lokasi')

@section('content')
<x-page-heading title="Jadwal & Lokasi" :description="'Aturan absensi siswa di '.$place->name.'.'"/>

<div class="schedule-grid">
    <form method="post" action="{{ route('mentor.schedule.update') }}" class="panel schedule-form">
        @csrf
        @method('PUT')

        <section>
            <h2>Lokasi absensi</h2>
            <div class="form-grid">
                <x-field name="latitude" label="Latitude" type="number" :value="$place->latitude ?? ''" step="any" min="-90" max="90"/>
                <x-field name="longitude" label="Longitude" type="number" :value="$place->longitude ?? ''" step="any" min="-180" max="180"/>
            </div>
            <div class="schedule-location">
                <x-button variant="secondary" data-use-location><x-icon name="place"/>Pakai lokasi saya sekarang</x-button>
                <small data-use-location-status aria-live="polite"></small>
            </div>
            <x-field name="attendance_radius" label="Radius absensi (meter)" type="number" :value="$place->attendance_radius" min="25" max="2000" step="1" required/>
        </section>

        <section>
            <h2>Jam & hari kerja</h2>
            <fieldset class="schedule-days">
                <legend>Hari kerja</legend>
                @foreach($dayNames as $day => $name)
                    <label class="schedule-day">
                        <input type="checkbox" name="work_days[]" value="{{ $day }}" @checked(in_array($day, old('work_days', $place->workDays())))>
                        <span>{{ mb_substr($name, 0, 3) }}</span>
                    </label>
                @endforeach
            </fieldset>
            @error('work_days')<p class="field-error">{{ $message }}</p>@enderror
            <div class="form-grid schedule-times">
                <x-field name="check_in_start" label="Absen masuk dibuka" type="time" :value="$place->scheduleTime('check_in_start')" required/>
                <x-field name="check_in_end" label="Batas tepat waktu" type="time" :value="$place->scheduleTime('check_in_end')" required hint="Setelah jam ini dicatat terlambat."/>
                <x-field name="check_out_start" label="Absen pulang dibuka" type="time" :value="$place->scheduleTime('check_out_start')" required/>
            </div>
        </section>

        <div class="actions"><x-button type="submit">Simpan jadwal</x-button></div>
    </form>

    <section class="panel">
        <h2>Hari libur</h2>
        <form method="post" action="{{ route('mentor.schedule.holidays.store') }}" class="schedule-holiday-form">
            @csrf
            <x-field name="name" label="Keterangan" maxlength="120" required/>
            <div class="form-grid">
                <x-field name="start_date" label="Mulai" type="date" required/>
                <x-field name="end_date" label="Sampai" type="date" required/>
            </div>
            <x-button type="submit" variant="secondary"><x-icon name="plus"/>Tambah libur</x-button>
        </form>

        @forelse($place->holidays as $holiday)
            <div class="review-row">
                <div>
                    <strong>{{ $holiday->name }}</strong>
                    <small>{{ $holiday->start_date->translatedFormat('j M Y') }}@if(! $holiday->start_date->equalTo($holiday->end_date)) – {{ $holiday->end_date->translatedFormat('j M Y') }}@endif</small>
                </div>
                <form method="post" action="{{ route('mentor.schedule.holidays.destroy', $holiday) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-button" aria-label="Hapus libur {{ $holiday->name }}"><x-icon name="trash"/></button>
                </form>
            </div>
        @empty
            <x-empty title="Belum ada hari libur" description="Siswa tidak dapat absen pada tanggal libur."/>
        @endforelse
    </section>
</div>
@endsection
