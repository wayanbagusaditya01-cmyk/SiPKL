@php
    $isMentor = in_array($role, ['pembina', 'pembina_sekolah', 'pembina_industri']);
    $descriptions = ['akun' => 'Buat dan perbarui akun. Profil peran harus lengkap sebelum aktivasi.', 'siswa' => $isMentor ? ($mentorScope['type'] === 'Industri' ? 'Siswa yang magang di tempat PKL kamu dalam data contoh.' : 'Siswa yang terhubung ke Pembina Sekolah dalam data contoh.') : 'Kelola identitas siswa dan relasinya dengan akun.', 'tempat' => 'Alamat, koordinat, dan kontak tempat kegiatan PKL.', 'pembina' => 'Profil pembina sekolah dan industri terhubung dengan akun masing-masing.', 'periode' => 'Atur rentang tanggal dan status pelaksanaan PKL.', 'penempatan' => 'Satu siswa memiliki satu penempatan sepanjang program sekolah.', 'absensi' => 'Catatan kehadiran dan waktu kegiatan PKL.', 'log-aktivitas' => 'Jejak aktivitas bersifat baca saja dan tidak memuat rahasia.'];
    $editing = $form && request('form') !== null;
    $editRecord = $editing && request('form') !== 'new' ? $lookup($form['key'], (int) request('form')) : null;
@endphp
<x-page-heading :title="$identity['menus'][$page]" :description="$descriptions[$page] ?? ''">
@if($form && !$editing)<x-button :href="$link($page, ['form' => 'new'])"><x-icon name="plus"/>{{ $form['title'] }}</x-button>@endif
</x-page-heading>
@if($editing)
    <section class="panel form-width"><div class="section-heading"><h2>{{ $editRecord ? 'Ubah '.$identity['menus'][$page] : $form['title'] }}</h2><a href="{{ $link($page) }}">Kembali ke daftar</a></div><p class="muted">Kolom bertanda * wajib diisi. Gunakan data contoh.</p>
    @if($page === 'akun')<x-alert>Pembuatan akun contoh tidak membuat kredensial atau memberikan hak akses. Reset kata sandi tersedia setelah autentikasi terhubung.</x-alert>@endif
    <form method="post" action="{{ route('preview.action', [$role, $page]) }}" data-submit @if($page === 'penempatan') data-confirm="Periksa ringkasan penempatan sebelum menyimpan data contoh." data-summary @endif>
    @csrf<input type="hidden" name="action" value="save">@if($editRecord)<input type="hidden" name="id" value="{{ $editRecord['id'] }}">@endif
    <div class="form-grid">@foreach($form['fields'] as $field)
        @if($editRecord && $field['name'] === 'account')<div class="field"><label>Akun terhubung</label><p>{{ $lookup('accounts', $editRecord['account'])['username'] }}</p></div>@else
        <x-field :name="$field['name']" :label="$field['label']" :type="$field['type']" :required="$field['required']" :options="$field['options']" :value="$editRecord[$field['name']] ?? ''" :step="$field['type'] === 'number' ? 'any' : null"/>
        @endif
    @endforeach</div>
    @if(in_array($page, ['siswa', 'pembina']) && !$editRecord && !$form['fields'][0]['options'])<x-alert>Akun yang memenuhi syarat belum tersedia. Admin perlu membuat dan menyiapkan akun contoh terlebih dahulu.</x-alert>@endif
    @if($page === 'penempatan')<div id="placement-preview" class="alert" role="status">Pilih tempat untuk melihat pembina industri yang sesuai.</div><script type="application/json" id="mentor-options">{!! json_encode(collect($data['mentors'])->where('type', 'Industri')->values(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>@endif
    <div class="actions"><x-button type="submit">{{ $page === 'penempatan' ? 'Tinjau Penempatan' : 'Simpan Data Contoh' }}</x-button><x-button variant="secondary" :href="$link($page)">Batal</x-button></div></form></section>
@else
    @if($role === 'siswa' && $page === 'absensi')
    @if(!$placement)<section class="panel"><x-empty title="Absensi belum tersedia" description="Koordinator perlu menetapkan penempatan aktif sebelum kamu mengisi absensi."/></section>@else
    <section class="panel form-width"><div class="section-heading"><h2>Isi absensi harian</h2><x-badge status="Data contoh"/></div><form action="{{ route('preview.action', [$role, $page]) }}" method="post" data-submit id="attendance-form">@csrf<input type="hidden" name="action" value="attendance"><div class="form-grid"><x-field name="date" label="Tanggal" type="date" required :value="config('preview.today')" min="2026-08-01" max="2026-10-31"/><x-field name="status" label="Status kehadiran" type="select" required :options="['Hadir' => 'Hadir', 'Izin' => 'Izin']" value="Hadir"/><div class="attendance-time"><x-field name="in" label="Jam masuk" type="time" required value="08:00"/></div><div class="attendance-time"><x-field name="out" label="Jam keluar" type="time" required value="16:00"/></div></div><x-field name="note" label="Catatan" type="textarea" hint="Catatan wajib untuk status Izin." maxlength="255"/><div class="actions"><x-button type="submit">Simpan Absensi</x-button></div><p class="muted">Satu catatan per tanggal. Koreksi setelah dikirim memerlukan konfirmasi Pembina. Alur koreksi belum terhubung.</p></form></section>@endif
    @endif
    @if($record)
    <section class="panel detail-panel"><div class="section-heading"><h2>Detail {{ $identity['menus'][$page] }}</h2><a href="{{ $link($page) }}">Tutup detail</a></div>
        @php
            $displayRow = collect(\App\Support\PreviewCatalog::table($role, $page, $data, $mentorScope)['rows'])->firstWhere('id', $record['id']);
        @endphp
        <dl>@foreach($displayRow['cells'] as $cell)<dt>{{ $table['headers'][$loop->index] }}</dt><dd>{{ $cell }}</dd>@endforeach
        @if($page === 'log-aktivitas')<dt>Detail aktivitas</dt><dd>{{ $record['detail'] }}</dd>@endif
        @if($page === 'siswa')<dt>Email contoh</dt><dd>{{ $record['email'] ?: 'Belum diisi' }}</dd><dt>Kehadiran tercatat</dt><dd>{{ collect($data['attendance'])->where('student', $record['id'])->count() }} catatan</dd><dt>Jurnal</dt><dd>{{ collect($data['journals'])->where('student', $record['id'])->count() }} entri</dd>@endif
        @if($page === 'penempatan')<dt>Pembina sekolah</dt><dd>{{ $lookup('mentors', $record['school'])['name'] }}</dd><dt>Pembina industri</dt><dd>{{ $lookup('mentors', $record['industry'])['name'] }}</dd>@endif</dl>
        @if($page === 'tempat')<x-button variant="secondary" :href="'https://www.google.com/maps/search/?api=1&query='.urlencode($record['latitude'].','.$record['longitude'])" target="_blank" rel="noopener noreferrer">Buka peta (tab baru)</x-button>@endif
        @if($page === 'akun')<p>{{ $record['profile'] ? 'Profil peran sudah terhubung.' : 'Profil peran belum terhubung. Aktivasi belum diizinkan.' }}</p><form method="post" action="{{ route('preview.action', [$role, $page]) }}" data-submit data-confirm="Ubah status akun contoh ini?">@csrf<input type="hidden" name="action" value="status"><input type="hidden" name="id" value="{{ $record['id'] }}"><x-field name="status" label="Status akun" type="select" :options="['Menunggu' => 'Menunggu', 'Aktif' => 'Aktif', 'Nonaktif' => 'Nonaktif']" :value="$record['status']" required/><div class="actions"><x-button type="submit">Ubah Status</x-button><x-button variant="secondary" disabled>Reset kata sandi</x-button></div><p class="muted">Reset belum tersedia karena autentikasi belum terhubung.</p></form>@endif
        @if($form && $page !== 'penempatan')<div class="actions"><x-button variant="secondary" :href="$link($page, ['form' => $record['id']])">Ubah Data</x-button></div>@endif
    </section>
    @endif
    <section class="panel table-panel">
    @if($page === 'pembina')<nav class="status-tabs" aria-label="Tipe pembina"><a href="{{ $link($page, ['status' => 'Sekolah']) }}" @if($filter === 'Sekolah') aria-current="page" @endif>Pembina Sekolah</a><a href="{{ $link($page, ['status' => 'Industri']) }}" @if($filter === 'Industri') aria-current="page" @endif>Pembina Industri</a></nav>@endif
    <form class="toolbar" method="get" action="{{ $link($page) }}"><div class="field search-field"><label for="search">Pencarian</label><div class="search-control"><x-icon name="search"/><input type="search" id="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Cari nama, identitas, atau kegiatan"></div></div>
    @if($statuses && array_filter($statuses))<div class="field"><label for="status-filter">{{ $page === 'pembina' ? 'Tipe pembina' : 'Status / peran' }}</label><select id="status-filter" name="status"><option value="">Semua</option>@foreach($statuses as $status)<option @selected($filter === $status)>{{ $status }}</option>@endforeach</select></div>@endif
    @php
        $additionalFilters = match($page) {
            'akun' => ['account_role' => ['Peran akun', collect($data['accounts'])->pluck('role')->unique()->all()]],
            'siswa' => ['class' => ['Kelas', collect($data['students'])->pluck('class')->unique()->all()], 'major' => ['Jurusan', collect($data['students'])->pluck('major')->unique()->all()]],
            'absensi' => $isMentor ? ['student' => [$mentorScope['type'] === 'Industri' ? 'Siswa magang' : 'Siswa bimbingan', collect($data['placements'])->filter(fn ($p) => \App\Support\PreviewMentor::guides($p, $mentorScope))->map(fn($p) => $lookup('students', $p['student'])['name'])->all()]] : [],
            'log-aktivitas' => ['actor' => ['Akun pelaku', collect($data['logs'])->pluck('actor')->unique()->all()], 'activity' => ['Tindakan', collect($data['logs'])->pluck('action')->unique()->all()], 'entity' => ['Entitas', collect($data['logs'])->pluck('entity')->unique()->all()]],
            default => []
        };
    @endphp
    @foreach($additionalFilters as $key => [$label, $options])<div class="field"><label for="filter-{{ $key }}">{{ $label }}</label><select name="{{ $key }}" id="filter-{{ $key }}"><option value="">Semua</option>@foreach($options as $option)<option @selected(request($key) === $option)>{{ $option }}</option>@endforeach</select></div>@endforeach
    @if(in_array($page, ['absensi', 'log-aktivitas']))<div class="field"><label for="from">Dari tanggal</label><input id="from" type="date" name="from" value="{{ request('from') }}"></div><div class="field"><label for="to">Sampai tanggal</label><input id="to" type="date" name="to" value="{{ request('to') }}"></div>@endif
    <x-button type="submit" variant="secondary">Terapkan</x-button><a class="reset-link" href="{{ $link($page) }}">Hapus filter</a></form>
    @if($table['rows'])<div class="table-scroll"><table class="responsive-table"><caption class="sr-only">Daftar {{ $identity['menus'][$page] }}</caption><thead><tr>@foreach($table['headers'] as $header)<th scope="col">{{ $header }}</th>@endforeach<th scope="col">Tindakan</th></tr></thead><tbody>@foreach($table['rows'] as $row)<tr>@foreach($row['cells'] as $cell)<td data-label="{{ $table['headers'][$loop->index] }}">@if(in_array($cell, ['Aktif', 'Nonaktif', 'Menunggu', 'Hadir', 'Izin', 'Ditempatkan', 'Belum ditempatkan', 'Selesai']))<x-badge :status="$cell"/>@else{{ $cell }}@endif</td>@endforeach<td data-label="Tindakan"><a class="row-action" href="{{ $link($page, ['detail' => $row['id']]) }}">Lihat detail</a></td></tr>@endforeach</tbody></table></div>
    <div class="pagination"><span>{{ $count }} hasil · Halaman {{ $current }} dari {{ max(1, (int) ceil($count / 8)) }}</span><nav aria-label="Halaman daftar">@if($current > 1)<a class="button secondary" href="{{ $link($page, array_replace(request()->query(), ['p' => $current - 1])) }}">Sebelumnya</a>@endif @if($current * 8 < $count)<a class="button secondary" href="{{ $link($page, array_replace(request()->query(), ['p' => $current + 1])) }}">Berikutnya</a>@endif</nav></div>
    @else<x-empty :title="$q || $filter || request('from') || request('to') ? 'Tidak ada hasil yang cocok' : 'Belum ada data'" :description="$q || $filter ? 'Coba kata pencarian lain atau hapus filter.' : 'Data akan tampil setelah dicatat dalam pratinjau.'"><x-button variant="secondary" :href="$link($page)">Hapus filter</x-button></x-empty>@endif
    </section>
@endif
