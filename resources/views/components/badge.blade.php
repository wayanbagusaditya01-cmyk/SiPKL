@props(['status'])
@php
    $tone = match (true) {
        in_array($status, ['Aktif', 'Hadir', 'Disetujui', 'Terbit', 'Ditempatkan', 'Selesai', 'Tepat waktu'], true) => 'success',
        in_array($status, ['Menunggu', 'Izin', 'Draf', 'Perlu Revisi', 'Belum ditempatkan', 'Belum dinilai'], true) => 'warning',
        in_array($status, ['Nonaktif', 'Ditolak', 'Terlambat'], true) => 'danger',
        default => 'neutral',
    };
@endphp
<span class="badge badge--{{ $tone }}">{{ $status }}</span>
