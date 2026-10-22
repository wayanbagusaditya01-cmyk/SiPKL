@extends('layouts.portal')

@section('title', 'Absensi')

@section('content')
<x-page-heading title="Absensi" description="Catat kehadiran pada rentang periode PKL aktif."/>
@if(!$period)
    <section class="panel"><x-empty title="Absensi belum tersedia" description="Absensi dapat diisi setelah Koordinator menetapkan penempatan dan periode aktif."/></section>
@else
    <section class="panel form-width"><h2>Isi absensi harian</h2><form method="post" action="{{ route('student.attendance.store') }}">
        @csrf
        <div class="form-grid"><x-field name="attendance_date" label="Tanggal" type="date" :value="old('attendance_date', now()->toDateString())" :min="$period->start_date->format('Y-m-d')" :max="$period->end_date->format('Y-m-d')" required/><x-field name="status" label="Status kehadiran" type="select" :options="['Hadir' => 'Hadir', 'Izin' => 'Izin']" :value="old('status', 'Hadir')" required/><x-field name="time_in" label="Jam masuk" type="time" :value="old('time_in')"/><x-field name="time_out" label="Jam keluar" type="time" :value="old('time_out')"/><x-field name="note" label="Catatan izin" type="textarea" :value="old('note')" maxlength="1000" hint="Wajib diisi untuk status Izin."/></div>
        <div class="actions"><x-button type="submit">Simpan Absensi</x-button></div>
    </form></section>
@endif
<section class="panel table-panel"><h2>Riwayat absensi</h2>
    @if($attendances->isNotEmpty())<div class="table-scroll"><table class="responsive-table"><caption class="sr-only">Riwayat absensi siswa</caption><thead><tr><th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col">Jam masuk</th><th scope="col">Jam keluar</th><th scope="col">Catatan</th></tr></thead><tbody>@foreach($attendances as $attendance)<tr><td data-label="Tanggal">{{ $attendance->attendance_date->format('d-m-Y') }}</td><td data-label="Status"><x-badge :status="$attendance->status"/></td><td data-label="Jam masuk">{{ $attendance->time_in ?: '—' }}</td><td data-label="Jam keluar">{{ $attendance->time_out ?: '—' }}</td><td data-label="Catatan">{{ $attendance->note ?: '—' }}</td></tr>@endforeach</tbody></table></div><div class="pagination"><span>{{ $attendances->total() }} catatan</span>{{ $attendances->links() }}</div>
    @else<x-empty title="Belum ada absensi" description="Absensi yang berhasil disimpan akan tampil di sini."/>@endif
</section>
@endsection