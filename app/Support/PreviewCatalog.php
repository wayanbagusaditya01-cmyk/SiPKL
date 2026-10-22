<?php

namespace App\Support;

class PreviewCatalog
{
    public static function forms(array $data): array
    {
        $options = fn ($key) => collect($data[$key])->mapWithKeys(fn ($item) => [$item['id'] => $item['name']])->all();
        $accounts = fn ($role) => collect($data['accounts'])->filter(fn ($a) => (is_array($role) ? in_array($a['role'], $role) : $a['role'] === $role) && ! $a['profile'] && ($role === 'Siswa' ? $a['status'] !== 'Nonaktif' : $a['status'] === 'Aktif'))->mapWithKeys(fn ($a) => [$a['id'] => $a['username']])->all();
        $field = fn ($name, $label, $type = 'text', $required = true, $options = []) => compact('name', 'label', 'type', 'required', 'options');

        return [
            'akun' => ['key' => 'accounts', 'title' => 'Buat Akun', 'fields' => [$field('name', 'Nama lengkap'), $field('username', 'Username'), $field('role', 'Peran', 'select', true, array_combine(['Siswa', 'Pembina Sekolah', 'Pembina Industri', 'Koordinator PKL', 'Admin'], ['Siswa', 'Pembina Sekolah', 'Pembina Industri', 'Koordinator PKL', 'Admin'])), $field('status', 'Status akun', 'select', true, ['Menunggu' => 'Menunggu', 'Aktif' => 'Aktif', 'Nonaktif' => 'Nonaktif'])]],
            'siswa' => ['key' => 'students', 'title' => 'Tambah Siswa', 'fields' => [$field('account', 'Akun Siswa yang belum terhubung', 'select', true, $accounts('Siswa')), $field('nis', 'NIS'), $field('name', 'Nama siswa'), $field('class', 'Kelas'), $field('major', 'Jurusan'), $field('email', 'Email', 'email', false)]],
            'tempat' => ['key' => 'places', 'title' => 'Tambah Tempat', 'fields' => [$field('name', 'Nama tempat'), $field('address', 'Alamat lengkap', 'textarea'), $field('latitude', 'Latitude', 'number'), $field('longitude', 'Longitude', 'number'), $field('contact', 'Kontak resmi'), $field('email', 'Email', 'email')]],
            'pembina' => ['key' => 'mentors', 'title' => 'Tambah Pembina', 'fields' => [$field('account', 'Akun Pembina aktif yang belum terhubung', 'select', true, $accounts(['Pembina Sekolah', 'Pembina Industri', 'Pembina PKL'])), $field('type', 'Tipe pembina', 'select', true, ['Sekolah' => 'Sekolah', 'Industri' => 'Industri']), $field('name', 'Nama pembina'), $field('email', 'Email', 'email'), $field('nip', 'NIP (Pembina Sekolah)', 'text', false), $field('position', 'Jabatan (Pembina Industri)', 'text', false), $field('phone', 'Nomor telepon', 'text', false), $field('place', 'Tempat PKL (Pembina Industri)', 'select', false, $options('places'))]],
            'periode' => ['key' => 'periods', 'title' => 'Buat Periode', 'fields' => [$field('name', 'Nama periode'), $field('year', 'Tahun ajaran', 'text'), $field('start', 'Tanggal mulai', 'date'), $field('end', 'Tanggal selesai', 'date'), $field('status', 'Status', 'select', true, ['Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'Menunggu' => 'Menunggu'])]],
            'penempatan' => ['key' => 'placements', 'title' => 'Tambah Penempatan', 'fields' => [$field('student', 'Siswa yang belum pernah ditempatkan', 'select', true, collect($data['students'])->reject(fn ($s) => collect($data['placements'])->contains('student', $s['id']))->mapWithKeys(fn ($s) => [$s['id'] => $s['name']])->all()), $field('place', 'Tempat PKL', 'select', true, $options('places')), $field('period', 'Periode aktif', 'select', true, collect($data['periods'])->where('status', 'Aktif')->mapWithKeys(fn ($p) => [$p['id'] => $p['name']])->all()), $field('school', 'Pembina sekolah', 'select', true, collect($data['mentors'])->where('type', 'Sekolah')->mapWithKeys(fn ($m) => [$m['id'] => $m['name']])->all()), $field('industry', 'Pembina industri sesuai tempat', 'select', true, collect($data['mentors'])->where('type', 'Industri')->mapWithKeys(fn ($m) => [$m['id'] => $m['name']])->all()), $field('date', 'Tanggal penempatan', 'date')]],
        ];
    }

    public static function table(string $role, string $page, array $data, array $mentorScope = ['type' => 'Sekolah', 'key' => 'school', 'id' => 5]): array
    {
        $isMentor = in_array($role, ['pembina', 'pembina_sekolah', 'pembina_industri']);
        $industry = $mentorScope['type'] === 'Industri';
        $guides = fn ($p) => PreviewMentor::guides($p, $mentorScope);
        $student = function ($id) use ($data, $role) {
            if ($role === 'siswa' && $id === 1 && auth()->check()) {
                return auth()->user()->name;
            }

            return collect($data['students'])->firstWhere('id', $id)['name'] ?? 'Tidak tersedia';
        };
        $place = fn ($id) => collect($data['places'])->firstWhere('id', $id)['name'] ?? 'Belum ditempatkan';
        $status = fn ($id) => collect($data['placements'])->contains('student', $id) ? 'Ditempatkan' : 'Belum ditempatkan';
        $scope = fn ($row) => $role === 'siswa' ? $row['student'] === 1 : (! $isMentor || collect($data['placements'])->contains(fn ($p) => $p['student'] === $row['student'] && $guides($p)));
        $result = match ($page) {
            'akun' => ['key' => 'accounts', 'headers' => ['Nama', 'Username', 'Peran', 'Status'], 'rows' => array_map(fn ($a) => ['id' => $a['id'], 'cells' => [$a['name'], $a['username'], $a['role'], $a['status']], 'status' => $a['status']], $data['accounts'])],
            'siswa' => ['key' => 'students', 'headers' => ['NIS', 'Nama siswa', 'Kelas', $isMentor ? ($industry ? 'Pembina Sekolah' : 'Tempat PKL') : 'Jurusan', 'Status PKL'], 'rows' => collect($data['students'])->filter(fn ($s) => ! $isMentor || collect($data['placements'])->contains(fn ($p) => $p['student'] === $s['id'] && $guides($p)))->map(fn ($s) => ['id' => $s['id'], 'cells' => [$s['nis'], $s['name'], $s['class'], $isMentor ? ($industry ? (collect($data['mentors'])->firstWhere('id', collect($data['placements'])->firstWhere('student', $s['id'])['school'] ?? 0)['name'] ?? 'Belum ditentukan') : $place(collect($data['placements'])->firstWhere('student', $s['id'])['place'] ?? 0)) : $s['major'], $status($s['id'])], 'status' => $status($s['id'])])->values()->all()],
            'tempat' => ['key' => 'places', 'headers' => ['Nama tempat', 'Alamat', 'Koordinat', 'Kontak'], 'rows' => array_map(fn ($p) => ['id' => $p['id'], 'cells' => [$p['name'], $p['address'], $p['latitude'].', '.$p['longitude'], $p['contact']], 'status' => ''], $data['places'])],
            'pembina' => ['key' => 'mentors', 'headers' => ['Nama', 'Tipe', 'Jabatan / NIP', 'Email', 'Tempat PKL'], 'rows' => array_map(fn ($m) => ['id' => $m['id'], 'cells' => [$m['name'], $m['type'], $m['type'] === 'Sekolah' ? ($m['nip'] ?: 'Belum diisi') : $m['position'], $m['email'], $m['place'] ? $place($m['place']) : 'Sekolah'], 'status' => $m['type']], $data['mentors'])],
            'periode' => ['key' => 'periods', 'headers' => ['Periode', 'Tahun ajaran', 'Mulai', 'Selesai', 'Status'], 'rows' => array_map(fn ($p) => ['id' => $p['id'], 'cells' => [$p['name'], $p['year'], $p['start'], $p['end'], $p['status']], 'status' => $p['status']], $data['periods'])],
            'penempatan' => ['key' => 'placements', 'headers' => ['Siswa', 'Tempat PKL', 'Periode', 'Tanggal', 'Status'], 'rows' => collect($data['placements'])->filter($scope)->map(fn ($p) => ['id' => $p['id'], 'cells' => [$student($p['student']), $place($p['place']), collect($data['periods'])->firstWhere('id', $p['period'])['name'], $p['date'], $p['status']], 'status' => $p['status']])->values()->all()],
            'absensi' => ['key' => 'attendance', 'headers' => ['Tanggal', 'Siswa', 'Status', 'Jam masuk', 'Jam keluar', 'Catatan'], 'rows' => collect($data['attendance'])->filter($scope)->sortByDesc('date')->map(fn ($a) => ['id' => $a['id'], 'cells' => [$a['date'], $student($a['student']), $a['status'], $a['in'] ?: 'Tidak berlaku', $a['out'] ?: 'Tidak berlaku', $a['note'] ?: 'Tidak ada catatan'], 'status' => $a['status']])->values()->all()],
            'log-aktivitas' => ['key' => 'logs', 'headers' => ['Waktu', 'Akun', 'Peran', 'Tindakan', 'Entitas'], 'rows' => collect($data['logs'])->sortByDesc('id')->map(fn ($l) => ['id' => $l['id'], 'cells' => [$l['time'], $l['actor'], $l['role'], $l['action'], $l['entity']], 'status' => $l['role']])->values()->all()],
            default => ['key' => '', 'headers' => [], 'rows' => []],
        };

        return $result;
    }
}
