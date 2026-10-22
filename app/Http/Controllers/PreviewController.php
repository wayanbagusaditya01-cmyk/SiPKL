<?php

namespace App\Http\Controllers;

use App\Models\Mentor;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Placement;
use App\Models\Student;
use App\Support\PreviewCatalog;
use App\Support\PreviewData;
use App\Support\PreviewMentor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PreviewController extends Controller
{
    private function enabled(): void
    {
        abort_unless(config('preview.enabled') && app()->environment(['local', 'testing']), 404);
    }

    private function data(Request $request): array
    {
        $data = $request->session()->get('sipkl_preview', PreviewData::initial());
        $user = $request->user();

        if ($user && $user->role === 'siswa') {
            $studentIndex = array_search(1, array_column($data['students'], 'id'));
            if ($studentIndex !== false) {
                $data['students'][$studentIndex]['name'] = $user->name;
                $data['students'][$studentIndex]['email'] = $user->email;
            }

            $accountIndex = array_search(1, array_column($data['accounts'], 'id'));
            if ($accountIndex !== false) {
                $data['accounts'][$accountIndex]['name'] = $user->name;
                $data['accounts'][$accountIndex]['username'] = $user->email;
            }
        }

        return $data;
    }

    private function context(string $role, string $page): array
    {
        $this->enabled();
        $roles = config('preview.roles');

        if ($role === 'pembina') {
            $variant = auth()->check() ? PreviewMentor::type(auth()->user()) : PreviewMentor::SCHOOL;
            $roleKey = $variant === PreviewMentor::INDUSTRY ? 'pembina_industri' : 'pembina_sekolah';
            $roles['pembina'] = $roles[$roleKey];
        }

        abort_unless(isset($roles[$role]['menus'][$page]), 404);

        if (in_array($role, ['pembina_sekolah', 'pembina_industri', 'pembina', 'siswa']) && auth()->check()) {
            $user = auth()->user();
            $roles[$role]['person'] = $user->name;
            $roles[$role]['initials'] = collect(explode(' ', trim($user->name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
        }

        return [$roles, $roles[$role]];
    }

    public function login()
    {
        $this->enabled();

        return view('preview.login', ['roles' => config('preview.roles')]);
    }

    public function show(Request $request, string $role, string $page = 'dashboard')
    {
        if ($request->user()->isMentor()) {
            return redirect()->route($this->mentorRoute($page));
        }

        [$roles, $identity] = $this->context($role, $page);
        if ($role === 'siswa' && $request->user()->student()->exists()) {
            return redirect()->route($this->studentRoute($page));
        }

        $data = $this->data($request);
        $studentProfile = $role === 'siswa'
            ? $request->user()->student()->with('placement.place')->first()
            : null;
        $realPlacement = $studentProfile?->placement;
        $coordinatorStudents = collect();
        $coordinatorUnplacedStudents = collect();
        $coordinatorPlacements = collect();

        if ($role === 'koordinator') {
            $coordinatorStudents = Student::query()
                ->with(['placement.place', 'placement.period', 'placement.schoolMentor.user', 'placement.industryMentor.user'])
                ->orderBy('name')
                ->orderBy('id')
                ->get();
            $coordinatorUnplacedStudents = $coordinatorStudents->filter(fn (Student $student): bool => $student->placement === null)->values();
            $coordinatorPlacements = $coordinatorStudents
                ->map(fn (Student $student): ?Placement => $student->placement)
                ->filter()
                ->sortByDesc('placed_at')
                ->values();
        }

        $mentorScope = PreviewMentor::scope($request->user());
        $forms = PreviewCatalog::forms($data);
        $table = PreviewCatalog::table($role, $page, $data, $mentorScope);
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('status', '');
        $studentFilter = (string) $request->query('student', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $extraFilters = array_filter($request->only(['class', 'major', 'account_role', 'actor', 'activity', 'entity']), fn ($value) => is_string($value) && $value !== '');
        Validator::make($request->query(), ['q' => 'nullable|string|max:100', 'from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', $from ? 'after_or_equal:from' : 'nullable']])->validate();
        $statuses = array_values(array_unique(array_column($table['rows'], 'status')));
        $allRows = $table['rows'];
        $table['rows'] = array_values(array_filter($allRows, function ($row) use ($q, $filter, $studentFilter, $from, $to, $extraFilters) {
            $cells = implode(' ', $row['cells']);
            foreach ($extraFilters as $value) {
                if (! in_array($value, $row['cells'], true)) {
                    return false;
                }
            }

            return ($q === '' || mb_stripos($cells, $q) !== false) && ($filter === '' || $row['status'] === $filter) && ($studentFilter === '' || str_contains($cells, $studentFilter)) && ($from === '' || substr($row['cells'][0], 0, 10) >= $from) && ($to === '' || substr($row['cells'][0], 0, 10) <= $to);
        }));
        $count = count($table['rows']);
        $current = max(1, min((int) $request->query('p', 1), max(1, (int) ceil($count / 8))));
        $table['rows'] = array_slice($table['rows'], ($current - 1) * 8, 8);
        $record = null;
        if ($request->filled('detail') && $table['key']) {
            $id = (int) $request->query('detail');
            abort_unless(collect($allRows)->contains('id', $id), 404);
            $record = collect($data[$table['key']])->firstWhere('id', $id);
        }
        $placement = $request->query('state') === 'unplaced' && $role === 'siswa' ? null : collect($data['placements'])->firstWhere('student', 1);
        $isMentor = in_array($role, ['pembina', 'pembina_sekolah', 'pembina_industri']);
        $journals = collect($data['journals'])->filter(fn ($j) => $role === 'siswa' ? $j['student'] === 1 : collect($data['placements'])->contains(fn ($p) => $p['student'] === $j['student'] && PreviewMentor::guides($p, $mentorScope)))->sortByDesc('date');
        if ($isMentor) {
            $journals = $journals->where('status', '!=', 'Draf');
        }
        if ($filter !== '') {
            $journals = $journals->where('status', $filter);
        }
        if ($q !== '') {
            $journals = $journals->filter(fn ($j) => mb_stripos($j['title'].' '.$j['activity'].' '.(collect($data['students'])->firstWhere('id', $j['student'])['name'] ?? ''), $q) !== false);
        }
        $selected = $request->filled('journal') ? $journals->firstWhere('id', (int) $request->query('journal')) : ($isMentor ? $journals->first() : null);
        abort_if($request->filled('journal') && ! $selected, 404);
        $form = ($role === 'admin' && $page === 'akun') || ($role === 'koordinator' && isset($forms[$page])) ? $forms[$page] : null;
        if ($request->filled('form')) {
            abort_unless($form, 404);
            if ($request->query('form') !== 'new') {
                abort_unless($page !== 'penempatan' && collect($data[$form['key']])->contains('id', (int) $request->query('form')), 404);
            }
        }

        return view('preview.app', compact('roles', 'identity', 'role', 'page', 'data', 'table', 'q', 'filter', 'statuses', 'count', 'current', 'record', 'placement', 'realPlacement', 'studentProfile', 'coordinatorStudents', 'coordinatorUnplacedStudents', 'coordinatorPlacements', 'journals', 'selected', 'form', 'mentorScope'));
    }

    private function profileKey(string $role, array $mentorScope): string
    {
        if ($role === 'pembina_industri' || ($role === 'pembina' && $mentorScope['type'] === PreviewMentor::INDUSTRY)) {
            return 'pembina-industri';
        }

        if ($role === 'pembina_sekolah') {
            return 'pembina';
        }

        return $role;
    }

    private function mentorRoute(string $page): string
    {
        return match ($page) {
            'dashboard' => 'mentor.dashboard',
            'siswa' => 'mentor.students.index',
            'penempatan' => 'mentor.placements.index',
            'absensi' => 'mentor.attendance.index',
            'jurnal' => 'mentor.journals.index',
            'penilaian' => 'mentor.grades.index',
            'profil' => 'mentor.profile.edit',
            default => abort(404),
        };
    }

    private function studentRoute(string $page): string
    {
        return match ($page) {
            'dashboard' => 'student.portal.dashboard',
            'penempatan' => 'student.portal.placement',
            'tempat-pkl' => 'student.portal.place',
            'pembina' => 'student.portal.mentors',
            'absensi' => 'student.attendance.index',
            'jurnal' => 'student.journals.index',
            'nilai' => 'student.portal.grade',
            default => abort(404),
        };
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function periodFor(array $data, int $student): array
    {
        $placement = collect($data['placements'])->firstWhere('student', $student);
        if (! $placement || $placement['status'] !== 'Aktif') {
            $this->invalid('date', 'Penempatan aktif belum tersedia. Hubungi Koordinator PKL.');
        }

        return collect($data['periods'])->firstWhere('id', $placement['period']);
    }

    public function action(Request $request, string $role, string $page)
    {
        abort_if($request->user()->isMentor(), 403, 'Gunakan portal Pembina untuk mengubah data.');

        [, $identity] = $this->context($role, $page);
        if ($role === 'siswa' && $request->user()->student()->exists()) {
            abort(403);
        }

        $mentorScope = PreviewMentor::scope($request->user());

        $data = $this->data($request);
        $action = $request->string('action')->toString();
        $message = 'Perubahan data contoh berhasil. Hanya berlaku dalam sesi pratinjau ini.';
        if ($role === 'siswa' && in_array($page, ['absensi', 'jurnal'])) {
            $period = $this->periodFor($data, 1);
            $rules = ['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$period['start'], 'before_or_equal:'.$period['end']]];
            if ($page === 'absensi') {
                abort_unless($action === 'attendance', 403);
                $values = $request->validate($rules + ['status' => ['required', Rule::in(['Hadir', 'Izin'])], 'in' => 'required_if:status,Hadir|nullable|date_format:H:i', 'out' => 'required_if:status,Hadir|nullable|date_format:H:i|after:in', 'note' => 'required_if:status,Izin|nullable|string|max:255']);
                if (collect($data['attendance'])->contains(fn ($a) => $a['student'] === 1 && $a['date'] === $values['date'])) {
                    $this->invalid('date', 'Absensi tanggal ini sudah ada. Koreksi memerlukan konfirmasi Pembina.');
                }
                $data['attendance'][] = ['id' => count($data['attendance']) + 1, 'student' => 1, 'date' => $values['date'], 'status' => $values['status'], 'in' => $values['status'] === 'Hadir' ? $values['in'] : '', 'out' => $values['status'] === 'Hadir' ? $values['out'] : '', 'note' => $values['note'] ?? ''];
            } else {
                abort_unless(in_array($action, ['draft', 'send']), 403);
                $values = $request->validate($rules + ['title' => 'required|string|max:120', 'activity' => 'required|string|max:10000', 'url' => 'nullable|url:http,https|max:255']);
                $id = $request->integer('id');
                $index = $id ? array_search($id, array_column($data['journals'], 'id')) : false;
                if ($id && ($index === false || $data['journals'][$index]['student'] !== 1 || ! in_array($data['journals'][$index]['status'], ['Draf', 'Perlu Revisi']))) {
                    abort(403);
                }
                $journal = ['id' => $id ?: max(array_column($data['journals'], 'id')) + 1, 'student' => 1, 'date' => $values['date'], 'title' => $values['title'], 'activity' => $values['activity'], 'url' => $values['url'] ?? '', 'status' => $action === 'draft' ? 'Draf' : 'Menunggu', 'note' => $index !== false ? $data['journals'][$index]['note'] : ''];
                if ($index === false) {
                    $data['journals'][] = $journal;
                } else {
                    $data['journals'][$index] = $journal;
                }
            }
        } elseif (in_array($role, ['pembina', 'pembina_sekolah', 'pembina_industri']) && $page === 'jurnal') {
            abort_unless(in_array($action, ['approve', 'revise']), 403);
            $values = $request->validate(['id' => 'required|integer', 'note' => ($action === 'revise' ? 'required' : 'nullable').'|string|max:3000']);
            $index = array_search($values['id'], array_column($data['journals'], 'id'));
            abort_if($index === false, 404);
            $j = $data['journals'][$index];
            abort_unless(collect($data['placements'])->contains(fn ($p) => $p['student'] === $j['student'] && PreviewMentor::guides($p, $mentorScope)) && $j['status'] === 'Menunggu', 403);
            $data['journals'][$index]['status'] = $action === 'approve' ? 'Disetujui' : 'Perlu Revisi';
            $data['journals'][$index]['note'] = $values['note'] ?? '';
        } elseif (in_array($role, ['pembina', 'pembina_sekolah', 'pembina_industri']) && $page === 'penilaian') {
            abort_unless(in_array($action, ['draft', 'publish']), 403);
            $values = $request->validate(['student' => 'required|integer', 'grade' => 'required|numeric|min:0|max:100', 'note' => 'nullable|string|max:3000']);
            abort_unless(collect($data['placements'])->contains(fn ($p) => $p['student'] === (int) $values['student'] && PreviewMentor::guides($p, $mentorScope)), 403);
            $data['grades'][(int) $values['student']] = ['grade' => $values['grade'], 'note' => $values['note'] ?? '', 'date' => config('preview.today'), 'status' => $action === 'draft' ? 'Draf' : 'Terbit'];
        } elseif (($role === 'admin' && $page === 'akun') || ($role === 'koordinator' && isset(PreviewCatalog::forms($data)[$page]))) {
            $form = PreviewCatalog::forms($data)[$page];
            $id = $request->integer('id');
            $index = $id ? array_search($id, array_column($data[$form['key']], 'id')) : false;
            abort_if($id && $index === false, 404);
            if ($action === 'status' && $role === 'admin') {
                $values = $request->validate(['status' => ['required', Rule::in(['Aktif', 'Nonaktif', 'Menunggu'])]]);
                abort_if($index === false, 404);
                if ($values['status'] === 'Aktif' && ! $data['accounts'][$index]['profile']) {
                    $this->invalid('status', 'Profil peran belum lengkap. Hubungkan profil melalui Koordinator sebelum aktivasi.');
                }
                $data['accounts'][$index]['status'] = $values['status'];
            } else {
                abort_unless($action === 'save', 403);
                $rules = [];
                foreach ($form['fields'] as $f) {
                    $rules[$f['name']] = [$f['required'] ? 'required' : 'nullable', match ($f['type']) {
                        'email' => 'email', 'date' => 'date_format:Y-m-d', 'number' => 'numeric', default => 'string'
                    }, $f['type'] === 'textarea' ? 'max:3000' : ($f['type'] === 'number' ? 'between:-180,180' : 'max:255')];
                    if ($f['type'] === 'select' && $f['name'] !== 'account') {
                        $rules[$f['name']][] = Rule::in(array_keys($f['options']));
                    }
                }
                if ($page === 'siswa' || $page === 'pembina') {
                    if ($id) {
                        unset($rules['account']);
                    } else {
                        $rules['account'] = ['required', Rule::in(array_keys($form['fields'][0]['options']))];
                    }
                }
                if ($page === 'periode') {
                    $rules['end'][] = 'after:start';
                    $rules['year'][] = 'regex:/^\d{4}\/\d{4}$/';
                }
                if ($page === 'tempat') {
                    $rules['latitude'][] = 'between:-90,90';
                }
                if ($page === 'pembina') {
                    $rules['place'][] = 'required_if:type,Industri';
                    $rules['phone'][] = 'required_if:type,Industri';
                    $rules['position'][] = 'required_if:type,Industri';
                }
                $values = $request->validate($rules);
                foreach (array_intersect(['username', 'nis', 'email'], array_keys($values)) as $unique) {
                    if ($values[$unique] && collect($data[$form['key']])->contains(fn ($r) => ($r[$unique] ?? null) === $values[$unique] && $r['id'] !== $id)) {
                        $this->invalid($unique, 'Nilai ini sudah digunakan. Gunakan nilai yang unik.');
                    }
                }
                if ($page === 'penempatan') {
                    if (collect($data['placements'])->contains(fn ($p) => $p['student'] === (int) $values['student'] && $p['id'] !== $id)) {
                        $this->invalid('student', 'Siswa hanya boleh memiliki satu penempatan sepanjang program sekolah.');
                    }
                    $mentor = collect($data['mentors'])->firstWhere('id', (int) $values['industry']);
                    if (! $mentor || $mentor['place'] !== (int) $values['place']) {
                        $this->invalid('industry', 'Pembina industri harus terhubung ke tempat yang dipilih.');
                    }
                    $period = collect($data['periods'])->firstWhere('id', (int) $values['period']);
                    if ($values['date'] < $period['start'] || $values['date'] > $period['end']) {
                        $this->invalid('date', 'Tanggal penempatan harus berada dalam rentang periode.');
                    }
                    $values['status'] = 'Aktif';
                }
                foreach (['account', 'student', 'place', 'period', 'school', 'industry'] as $numeric) {
                    if (isset($values[$numeric]) && $values[$numeric] !== '') {
                        $values[$numeric] = (int) $values[$numeric];
                    }
                }
                if ($page === 'pembina') {
                    $values['place'] = $values['type'] === 'Industri' ? $values['place'] : null;
                    $values += ['nip' => '', 'position' => '', 'phone' => ''];
                    if ($index !== false && $values['type'] !== $data['mentors'][$index]['type']) {
                        $this->invalid('type', 'Tipe profil yang sudah terhubung tidak dapat diubah dalam pratinjau.');
                    }
                }
                $values['id'] = $id ?: max(array_column($data[$form['key']], 'id')) + 1;
                if (! $id && isset($values['account'])) {
                    $values['id'] = $values['account'];
                }
                if ($page === 'akun') {
                    $values['profile'] = $index !== false ? $data['accounts'][$index]['profile'] : false;
                    if ($values['status'] === 'Aktif' && ! $values['profile']) {
                        $this->invalid('status', 'Lengkapi profil sebelum mengaktifkan akun.');
                    }
                    if ($index !== false && $values['profile'] && $values['role'] !== $data['accounts'][$index]['role']) {
                        $this->invalid('role', 'Peran akun dengan profil terhubung tidak dapat diganti dalam pratinjau.');
                    }
                }
                if ($index === false) {
                    $data[$form['key']][] = $values;
                    if (isset($values['account'])) {
                        foreach ($data['accounts'] as &$account) {
                            if ($account['id'] === $values['account']) {
                                $account['profile'] = true;
                            }
                        }
                        unset($account);
                    }
                } else {
                    $data[$form['key']][$index] = array_replace($data[$form['key']][$index], $values);
                }
            }
        } elseif ($page === 'profil' && in_array($role, ['admin', 'pembina', 'pembina_sekolah', 'pembina_industri'])) {
            abort_unless($action === 'profile', 403);
            $data['profiles'][$this->profileKey($role, $mentorScope)] = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:120', 'phone' => 'nullable|string|max:20']);
        } else {
            abort(403);
        }
        $data['logs'][] = ['id' => count($data['logs']) + 1, 'time' => config('preview.today').' '.now('Asia/Makassar')->format('H:i'), 'actor' => $role.'.contoh', 'role' => $identity['name'], 'action' => 'Simulasi '.$action, 'entity' => $page, 'detail' => 'Perubahan data contoh dalam sesi pratinjau.'];
        $request->session()->put('sipkl_preview', $data);

        return redirect()->route('preview.page', [$role, $page])->with('success', $message);
    }

    public function reset(Request $request)
    {
        $this->enabled();
        $request->session()->forget('sipkl_preview');

        return redirect()->route('login')->with('success', 'Data contoh sudah dikembalikan ke kondisi awal.');
    }

    public function activateStudent(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'siswa', 403);

        $student = $user->ensureStudent();

        $activePeriod = PklPeriod::where('status', 'Aktif')->first();
        if (! $activePeriod) {
            $activePeriod = PklPeriod::create([
                'name' => 'PKL 2026/2027',
                'academic_year' => '2026/2027',
                'start_date' => '2026-08-01',
                'end_date' => '2026-10-31',
                'status' => 'Aktif',
            ]);
        }

        if (! $student->placement()->exists()) {
            $place = Place::first() ?? Place::create([
                'name' => 'Studio Nusa Digital',
                'address' => 'Jl. Pendidikan No. 12, Makassar, Sulawesi Selatan',
                'contact' => '08123456789',
                'email' => 'studio@nusadigital.test',
            ]);

            $schoolMentor = Mentor::where('type', 'Sekolah')->first();

            Placement::create([
                'student_id' => $student->id,
                'place_id' => $place->id,
                'period_id' => $activePeriod->id,
                'school_mentor_id' => $schoolMentor?->id,
                'placed_at' => '2026-08-01',
                'status' => 'Aktif',
            ]);
        }

        return redirect()->route('student.portal.dashboard')->with('success', 'Akun siswa berhasil dihubungkan ke portal database utama.');
    }
}
