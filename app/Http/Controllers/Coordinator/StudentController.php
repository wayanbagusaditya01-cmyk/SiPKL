<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'class' => ['nullable', 'string', 'max:100'],
            'major' => ['nullable', 'string', 'max:150'],
        ]);

        $students = Student::query()
            ->with(['user:id,name,email', 'placement.place:id,name'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nis', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('class', 'like', '%'.$search.'%')
                        ->orWhere('major', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('placement.place', fn (Builder $placeQuery): Builder => $placeQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['class'] ?? null, fn (Builder $query, string $class): Builder => $query->where('class', $class))
            ->when($filters['major'] ?? null, fn (Builder $query, string $major): Builder => $query->where('major', $major))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.students.index', [
            'students' => $students,
            'classOptions' => Student::query()->select('class')->distinct()->orderBy('class')->pluck('class'),
            'majorOptions' => Student::query()->select('major')->distinct()->orderBy('major')->pluck('major'),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('coordinator.students.form', [
            'student' => null,
            'accounts' => $this->availableAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validatedValues($request));

        return redirect()->route('coordinator.siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Student $student): View
    {
        return view('coordinator.students.form', [
            'student' => $student,
            'accounts' => $this->availableAccounts($student),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validatedValues($request, $student));

        return redirect()->route('coordinator.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    private function validatedValues(Request $request, ?Student $student = null): array
    {
        $values = $request->validate($this->rules($student));
        $values['user_id'] = $values['user_id'] ?? null;
        $values['email'] = isset($values['email']) ? Str::lower(trim($values['email'])) : null;

        return $values;
    }

    private function rules(?Student $student = null): array
    {
        $nisRule = Rule::unique('students', 'nis');
        $accountRule = Rule::unique('students', 'user_id');

        if ($student) {
            $nisRule->ignore($student->id);
            $accountRule->ignore($student->id);
        }

        return [
            'nis' => ['required', 'string', 'max:32', $nisRule],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:100'],
            'major' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'siswa')->where('status', true)),
                $accountRule,
            ],
        ];
    }

    private function availableAccounts(?Student $student = null): Collection
    {
        return User::query()
            ->where('role', 'siswa')
            ->where('status', true)
            ->where(function (Builder $query) use ($student): void {
                $query->whereDoesntHave('student');

                if ($student?->user_id) {
                    $query->orWhereKey($student->user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
