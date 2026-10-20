@php
    $guided = collect($data['placements'])->filter(fn ($p) => \App\Support\PreviewMentor::guides($p, $mentorScope));
    $selectedStudent = (int) old('student', request('student', 1));
    $grade = $data['grades'][$selectedStudent] ?? null;
    $gradePlacement = $guided->firstWhere('student', $selectedStudent);
@endphp
<x-page-heading title="Penilaian PKL" description="Simpan evaluasi sebagai draf, kemudian terbitkan saat sudah siap."/>
<section class="panel form-width"><form action="{{ $link('penilaian') }}" method="get" class="toolbar"><div class="field"><label for="student-picker">{{ $mentorScope['type'] === 'Industri' ? 'Pilih siswa magang' : 'Pilih siswa bimbingan' }}</label><select id="student-picker" name="student">@foreach($guided as $p)<option value="{{ $p['student'] }}" @selected($selectedStudent === $p['student'])>{{ $lookup('students', $p['student'])['name'] }}</option>@endforeach</select></div><x-button type="submit" variant="secondary">Buka Penilaian</x-button></form>
@if(!$gradePlacement)<x-empty title="Siswa bimbingan belum dipilih" description="Pilih siswa yang terhubung ke akun Pembina dalam pratinjau."/>
@else
<div class="section-heading"><h2>{{ $lookup('students', $selectedStudent)['name'] }}</h2><x-badge :status="$grade['status'] ?? 'Belum dinilai'"/></div><dl><dt>Periode</dt><dd>{{ $lookup('periods', $gradePlacement['period'])['name'] }}</dd><dt>Tempat PKL</dt><dd>{{ $lookup('places', $gradePlacement['place'])['name'] }}</dd></dl>
<form method="post" action="{{ route('preview.action', [$role, $page]) }}" data-submit data-confirm-publish>@csrf<input type="hidden" name="student" value="{{ $selectedStudent }}"><x-field name="grade" label="Nilai akhir (0 sampai 100)" type="number" required min="0" max="100" step="0.01" :value="$grade['grade'] ?? ''"/><x-field name="note" label="Catatan penilaian" type="textarea" :value="$grade['note'] ?? ''" maxlength="3000"/><div class="actions"><x-button type="submit" name="action" value="publish">Terbitkan Nilai</x-button><x-button type="submit" name="action" value="draft" variant="secondary">Simpan Draf</x-button></div><p class="muted">Draf tidak terlihat oleh Siswa. Penerbitan membuat nilai dan catatan contoh tampil di pratinjau Siswa. Tidak ada formula atau bobot otomatis.</p></form>@endif</section>
