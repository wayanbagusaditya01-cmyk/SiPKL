<x-page-heading title="Hak Akses" description="Matriks empat peran yang mengikuti lingkup tugas SIPKL."/>
<section class="panel"><x-alert>Matriks ini menunjukkan rancangan izin. Perubahan izin dan otorisasi produksi belum tersedia dalam pratinjau.</x-alert><div class="table-scroll"><table><caption class="sr-only">Matriks hak akses empat peran</caption><thead><tr><th scope="col">Fitur</th><th scope="col">Admin</th><th scope="col">Koordinator PKL</th><th scope="col">Pembina PKL</th><th scope="col">Siswa</th></tr></thead><tbody>@foreach([
['Akun & akses', 'Kelola', 'Tidak tersedia', 'Tidak tersedia', 'Tidak tersedia'],
['Data operasional', 'Tidak tersedia', 'Kelola', 'Baca bimbingan', 'Baca milik sendiri'],
['Penempatan', 'Tidak tersedia', 'Kelola', 'Baca bimbingan', 'Baca milik sendiri'],
['Absensi', 'Tidak tersedia', 'Pantau', 'Pantau bimbingan', 'Isi milik sendiri'],
['Jurnal', 'Tidak tersedia', 'Pantau', 'Verifikasi bimbingan', 'Isi milik sendiri'],
['Nilai', 'Tidak tersedia', 'Pantau', 'Kelola bimbingan', 'Baca hasil terbit'],
['Audit & konfigurasi', 'Kelola / baca audit', 'Tidak tersedia', 'Tidak tersedia', 'Tidak tersedia']
] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></div><div class="actions"><x-button disabled>Simpan Hak Akses</x-button></div><p class="muted">Memerlukan backend policy atau gate dan penyimpanan izin. Tidak ada penambahan peran.</p></section>
