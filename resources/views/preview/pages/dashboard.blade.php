@php
    $ownAttendance = collect($data['attendance'])->where('student', 1);
    $ownJournals = collect($data['journals'])->where('student', 1);
    $isMentor = in_array($role, ['pembina', 'pembina_sekolah', 'pembina_industri']);
    $isIndustry = $role === 'pembina_industri' || ($role === 'pembina' && $mentorScope['type'] === 'Industri');
    $waiting = collect($data['journals'])->where('status', 'Menunggu')->when($isMentor, fn ($journals) => $journals->filter(fn ($j) => collect($data['placements'])->contains(fn ($p) => $p['student'] === $j['student'] && \App\Support\PreviewMentor::guides($p, $mentorScope))));
    $title = match (true) {
        $role === 'siswa' => 'Ringkasan PKL Saya',
        $isMentor => $isIndustry ? 'Ringkasan Pembimbingan' : 'Ringkasan Bimbingan',
        $role === 'koordinator' => 'Ringkasan Operasional',
        default => 'Ringkasan Sistem',
    };
    $description = match (true) {
        $role === 'siswa' => 'PKL 2026 · 1 Agustus sampai 31 Oktober 2026',
        $isMentor => $isIndustry ? 'Kegiatan siswa di tempat PKL yang perlu ditindaklanjuti.' : 'Tugas dan kegiatan siswa yang perlu ditindaklanjuti.',
        $role === 'koordinator' => 'Data siswa dan penempatan yang tersimpan di database.',
        default => 'Status akun dan aktivitas terbaru dalam satu ringkasan.',
    };
@endphp
<x-page-heading :title="$title" :description="$description"><span class="date-label">Kamis, 8 Oktober 2026</span></x-page-heading>
@if($role === 'siswa')
    @if($studentProfile)
        @if(!$realPlacement)
            <section class="panel"><x-empty title="Penempatan belum ditetapkan" description="Koordinator PKL belum menetapkan tempat untuk profilmu."/></section>
        @else
            <section class="panel placement-summary"><h2>Penempatan saya</h2><span class="round-icon"><x-icon name="place"/></span><h3>{{ $realPlacement->place->name }}</h3><x-badge :status="$realPlacement->status"/><dl><dt>Alamat</dt><dd>{{ $realPlacement->place->address ?: 'Belum dicatat' }}</dd><dt>Tanggal penempatan</dt><dd>{{ $realPlacement->placed_at?->format('d-m-Y') ?? 'Belum dicatat' }}</dd></dl><x-button variant="secondary" :href="$link('penempatan')">Lihat penempatan</x-button></section>
        @endif
    @else
    @if(!$placement)<section class="panel"><x-empty title="Penempatan belum tersedia" description="Koordinator PKL akan menentukan tempat dan pembinamu. Absensi dan jurnal tersedia setelah penempatan aktif."><x-button variant="secondary" :href="$link('dashboard')">Kembali ke contoh dengan penempatan</x-button></x-empty></section>
    @else
    <section class="daily-actions panel" aria-label="Kegiatan hari ini"><div><span class="round-icon"><x-icon name="calendar"/></span><div><h2>{{ $ownAttendance->contains('date', config('preview.today')) ? 'Absensi hari ini sudah diisi.' : 'Absensi hari ini belum diisi.' }}</h2><p>Catat kehadiranmu sebelum memulai kegiatan.</p><x-button :href="$link('absensi')">Isi Absensi</x-button></div></div><div><span class="round-icon"><x-icon name="file"/></span><div><h2>{{ $ownJournals->contains('status', 'Perlu Revisi') ? 'Ada jurnal yang perlu diperbaiki.' : 'Catat pembelajaran hari ini.' }}</h2><p>Baca catatan pembina dan kirim ulang jurnalmu.</p><x-button variant="secondary" :href="$link('jurnal')">Tulis Jurnal</x-button></div></div></section>
    <div class="stats"><x-stat label="Kehadiran tercatat" :value="$ownAttendance->where('status', 'Hadir')->count().' / '.$ownAttendance->count().' hari'" caption="Dari riwayat data contoh"/><x-stat label="Jurnal terkirim" :value="$ownJournals->where('status', '!=', 'Draf')->count().' entri'"/><x-stat label="Menunggu tinjauan" :value="$ownJournals->where('status', 'Menunggu')->count().' jurnal'"/></div>
    <div class="content-grid"><section class="panel"><div class="section-heading"><h2>Aktivitas terbaru</h2><a href="{{ $link('jurnal') }}">Lihat semua</a></div><div class="activity-list">@foreach($ownJournals->sortByDesc('date') as $journal)<div class="activity-row"><span class="activity-date">{{ $journal['date'] }}</span><div><strong>{{ $journal['title'] }}</strong><small>{{ $journal['note'] ?: 'Jurnal kegiatan di tempat PKL' }}</small></div><x-badge :status="$journal['status']"/><a class="row-action" href="{{ $link('jurnal', ['journal' => $journal['id']]) }}">Lihat</a></div>@endforeach</div></section><section class="panel placement-summary"><h2>Penempatan saya</h2><span class="round-icon"><x-icon name="place"/></span><h3>{{ $lookup('places', $placement['place'])['name'] }}</h3><x-badge status="Aktif"/><dl><dt>Alamat</dt><dd>{{ $lookup('places', $placement['place'])['address'] }}</dd><dt>Periode PKL</dt><dd>1 Agustus sampai 31 Oktober 2026</dd><dt>Pembina sekolah</dt><dd>{{ $lookup('mentors', $placement['school'])['name'] }}</dd><dt>Pembina industri</dt><dd>{{ $lookup('mentors', $placement['industry'])['name'] }}</dd></dl><x-button variant="secondary" :href="$link('penempatan')">Lihat penempatan</x-button></section></div>
    @php($grade = $data['grades'][1] ?? null)
    <x-alert>{{ $grade && $grade['status'] === 'Terbit' ? 'Nilai sudah diterbitkan. Lihat hasil dan catatan pada halaman Nilai & Catatan.' : 'Nilai belum diterbitkan. Hasil penilaian akan tampil setelah diterbitkan Pembina.' }}</x-alert>
    @endif
    <a class="scenario-link" href="{{ $link('dashboard', ['state' => 'unplaced']) }}">Periksa keadaan belum ada penempatan</a>
    @endif
@elseif($isMentor)
    @php($guided = collect($data['placements'])->filter(fn ($p) => \App\Support\PreviewMentor::guides($p, $mentorScope)))
    <div class="stats"><x-stat :label="$isIndustry ? 'Siswa magang' : 'Siswa bimbingan'" :value="$guided->count().' siswa'"/><x-stat label="Jurnal menunggu" :value="$waiting->count().' jurnal'"/><x-stat label="Hadir hari ini" :value="collect($data['attendance'])->where('date', config('preview.today'))->where('status', 'Hadir')->whereIn('student', $guided->pluck('student'))->count().' / '.$guided->count()"/></div>
    <div class="content-grid"><section class="panel"><div class="section-heading"><div><h2>Antrean tinjauan</h2><p>Mulai dari jurnal yang menunggu keputusanmu.</p></div><x-button :href="$link('jurnal', ['status' => 'Menunggu'])">Tinjau Jurnal</x-button></div>@forelse($waiting as $journal)<div class="review-row"><span class="avatar">{{ mb_substr($lookup('students', $journal['student'])['name'], 0, 1) }}</span><div><strong>{{ $lookup('students', $journal['student'])['name'] }}</strong><p>{{ $journal['title'] }}</p><small>{{ $journal['date'] }}</small></div><a class="row-action" href="{{ $link('jurnal', ['journal' => $journal['id']]) }}">Tinjau</a></div>@empty<x-empty title="Semua jurnal sudah ditinjau" description="Jurnal baru akan muncul setelah siswa mengirimkannya."/>@endforelse</section><section class="panel"><h2>Perlu tindak lanjut</h2>@foreach($guided as $p)<div class="follow-up"><strong>{{ $lookup('students', $p['student'])['name'] }}</strong><p>{{ collect($data['attendance'])->contains(fn ($a) => $a['student'] === $p['student'] && $a['date'] === config('preview.today')) ? 'Kehadiran hari ini tercatat.' : 'Absensi hari ini belum diisi.' }}</p><a href="{{ $link('siswa', ['detail' => $p['student']]) }}">Lihat siswa</a></div>@endforeach</section></div>
@elseif($role === 'koordinator')
    <div class="stats"><x-stat label="Siswa peserta" :value="$coordinatorStudents->count().' siswa'"/><x-stat label="Penempatan aktif" :value="$coordinatorPlacements->where('status', 'Aktif')->count().' siswa'"/><x-stat label="Belum ditempatkan" :value="$coordinatorUnplacedStudents->count().' siswa'"/></div>
    <section class="panel"><div class="section-heading"><div><h2>Prioritas operasional</h2><p>Selesaikan penempatan agar siswa dapat memulai kegiatan.</p></div><x-button :href="route('coordinator.penempatan.create')"><x-icon name="plus"/>Tambah Penempatan</x-button></div>
        @forelse($coordinatorUnplacedStudents as $student)
            <div class="review-row"><span class="avatar">{{ mb_substr($student->name, 0, 1) }}</span><div><strong>{{ $student->name }}</strong><small>{{ $student->nis }} · {{ $student->class }}</small></div><x-badge status="Belum ditempatkan"/><a class="row-action" href="{{ route('coordinator.penempatan.create') }}">Tempatkan</a></div>
        @empty
            <x-empty title="Tidak ada siswa yang menunggu penempatan" description="Siswa yang belum terdaftar atau belum ditempatkan akan muncul di sini."/>
        @endforelse
        <div class="follow-up"><strong>Jurnal dan absensi</strong><p>Ringkasan jurnal dan absensi belum tersedia karena datanya belum tersimpan di database.</p><a href="{{ $link('laporan') }}">Lihat laporan PKL</a></div>
    </section>
    <section class="panel"><div class="section-heading"><h2>Penempatan terbaru</h2><a href="{{ $link('penempatan') }}">Lihat semua</a></div>
        @forelse($coordinatorPlacements->take(5) as $coordinatorPlacement)
            <div class="review-row"><div><strong>{{ $coordinatorPlacement->student->name }}</strong><p>{{ $coordinatorPlacement->place->name }}</p></div><x-badge :status="$coordinatorPlacement->status"/><small>{{ $coordinatorPlacement->placed_at?->format('d-m-Y') ?? 'Tanggal belum diisi' }}</small></div>
        @empty
            <x-empty title="Belum ada penempatan" description="Penempatan dari menu Koordinator akan ditampilkan di sini."/>
        @endforelse
    </section>
@else
    <div class="stats four"><x-stat label="Total akun" :value="count($data['accounts'])"/><x-stat label="Akun aktif" :value="collect($data['accounts'])->where('status', 'Aktif')->count()"/><x-stat label="Akun nonaktif" :value="collect($data['accounts'])->where('status', 'Nonaktif')->count()"/><x-stat label="Akun menunggu" :value="collect($data['accounts'])->where('status', 'Menunggu')->count()"/></div>
    <div class="content-grid"><section class="panel"><div class="section-heading"><div><h2>Akun perlu ditindaklanjuti</h2><p>Periksa kelengkapan profil sebelum aktivasi.</p></div><x-button :href="$link('akun')">Kelola Akun</x-button></div>@foreach(collect($data['accounts'])->where('profile', false) as $account)<div class="review-row"><span class="avatar">{{ mb_substr($account['name'], 0, 1) }}</span><div><strong>{{ $account['name'] }}</strong><small>{{ $account['username'] }} · Profil belum terhubung</small></div><x-badge :status="$account['status']"/><a class="row-action" href="{{ $link('akun', ['detail' => $account['id']]) }}">Periksa</a></div>@endforeach</section><section class="panel"><h2>Akun per peran</h2>@foreach($roles as $item)<div class="distribution"><span>{{ $item['name'] }}</span><strong>{{ collect($data['accounts'])->where('role', $item['name'])->count() }}</strong></div>@endforeach</section></div>
    <section class="panel"><div class="section-heading"><h2>Aktivitas terbaru</h2><a href="{{ $link('log-aktivitas') }}">Lihat log</a></div>@foreach(collect($data['logs'])->sortByDesc('id')->take(4) as $log)<div class="review-row"><span class="round-icon"><x-icon name="file"/></span><div><strong>{{ $log['action'] }}</strong><small>{{ $log['actor'] }} · {{ $log['entity'] }}</small></div><span class="muted">{{ $log['time'] }}</span></div>@endforeach</section>
@endif
