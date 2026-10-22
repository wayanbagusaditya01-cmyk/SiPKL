@props(['status'])
<span class="badge {{ in_array($status, ['Aktif', 'Hadir', 'Disetujui', 'Terbit', 'Ditempatkan']) ? 'blue' : '' }}">{{ $status }}</span>
