<x-page-heading :title="$identity['menus'][$page]" description="Informasi PKL yang terhubung ke siswa dalam pratinjau ini."/>
@if(!$placement)
<section class="panel"><x-empty title="Penempatan belum tersedia" description="Penempatan ditentukan Koordinator PKL. Informasi tempat, pembina, dan nilai akan tersedia setelah penempatan ditetapkan."/></section>
@else
@php($place = $lookup('places', $placement['place']))
@if($page === 'nilai')
    @php($grade = $data['grades'][1] ?? null)
    @if(!$grade || $grade['status'] !== 'Terbit')<section class="panel"><x-empty title="Nilai belum diterbitkan" description="Pembina sedang menyiapkan evaluasi PKL. Nilai dan catatan akan tampil setelah hasilnya diterbitkan."/></section>
    @else<section class="panel form-width"><div class="section-heading"><h2>Hasil penilaian PKL</h2><x-badge status="Terbit"/></div><div class="grade-value">{{ $grade['grade'] }}<span>/ 100</span></div><dl><dt>Catatan penilaian</dt><dd class="preserve-lines">{{ $grade['note'] ?: 'Tidak ada catatan tambahan.' }}</dd><dt>Tanggal penilaian</dt><dd>{{ $grade['date'] }}</dd></dl></section>@endif
@elseif($page === 'pembina')
    <div class="two-column">@foreach(['school' => 'Pembina Sekolah', 'industry' => 'Pembina Industri'] as $key => $label)@php($mentor = $lookup('mentors', $placement[$key]))<section class="panel"><span class="round-icon"><x-icon name="user"/></span><p class="muted">{{ $label }}</p><h2>{{ $mentor['name'] }}</h2><dl><dt>Unit / tempat</dt><dd>{{ $key === 'school' ? 'Sekolah' : $place['name'] }}</dd><dt>Email contoh</dt><dd>{{ $mentor['email'] }}</dd><dt>Kontak resmi</dt><dd>{{ $mentor['phone'] ?: 'Belum tersedia dalam data contoh.' }}</dd></dl><p class="muted">Gunakan kanal resmi sekolah atau tempat PKL untuk bimbingan.</p></section>@endforeach</div>
@elseif($page === 'tempat-pkl')
    <section class="panel form-width"><span class="round-icon"><x-icon name="place"/></span><h2>{{ $place['name'] }}</h2><dl><dt>Alamat</dt><dd>{{ $place['address'] }}</dd><dt>Koordinat</dt><dd>{{ $place['latitude'] }}, {{ $place['longitude'] }}</dd><dt>Kontak</dt><dd>{{ $place['contact'] }}</dd><dt>Email contoh</dt><dd>{{ $place['email'] }}</dd></dl><x-button variant="secondary" :href="'https://www.google.com/maps/search/?api=1&query='.urlencode($place['latitude'].','.$place['longitude'])" target="_blank" rel="noopener noreferrer">Buka peta (tab baru)</x-button><p class="muted">Alamat dan koordinat tetap tersedia tanpa layanan peta.</p></section>
@else
    <section class="panel form-width"><div class="section-heading"><h2>{{ $place['name'] }}</h2><x-badge :status="$placement['status']"/></div><dl class="detail-grid"><dt>Periode</dt><dd>{{ $lookup('periods', $placement['period'])['name'] }}</dd><dt>Rentang tanggal</dt><dd>{{ $lookup('periods', $placement['period'])['start'] }} sampai {{ $lookup('periods', $placement['period'])['end'] }}</dd><dt>Tanggal penempatan</dt><dd>{{ $placement['date'] }}</dd><dt>Pembina sekolah</dt><dd>{{ $lookup('mentors', $placement['school'])['name'] }}</dd><dt>Pembina industri</dt><dd>{{ $lookup('mentors', $placement['industry'])['name'] }}</dd></dl><div class="actions"><x-button :href="$link('tempat-pkl')">Buka detail tempat</x-button><x-button variant="secondary" :href="$link('pembina')">Lihat Pembina</x-button></div></section>
@endif
@endif
<a class="scenario-link" href="{{ $link($page, ['state' => 'unplaced']) }}">Periksa keadaan belum ada penempatan</a>
