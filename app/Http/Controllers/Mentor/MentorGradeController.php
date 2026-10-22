<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\PklGrade;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MentorGradeController extends Controller
{
    use ResolvesMentor;

    public function index(Request $request): View
    {
        $mentor = $this->mentorFor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'student' => ['nullable', 'integer'],
        ]);

        $placements = $mentor->guidedPlacements()
            ->with([
                'student.grades' => fn ($query) => $query->where('mentor_id', $mentor->id),
                'place',
            ])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('student', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%');
                });
            })
            ->get()
            ->sortBy(fn ($placement) => $placement->student?->name)
            ->values();

        $grading = null;
        $currentGrade = null;

        if (isset($filters['student'])) {
            abort_unless($mentor->guides((int) $filters['student']), 403);
            $grading = Student::findOrFail($filters['student']);
            $currentGrade = $mentor->grades()->where('student_id', $grading->id)->first();
        }

        $q = $filters['q'] ?? '';

        return view('mentor.grades.index', compact('mentor', 'placements', 'q', 'grading', 'currentGrade'));
    }

    public function store(Request $request): RedirectResponse
    {
        $mentor = $this->mentorFor($request);

        $values = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($mentor->guidedStudentIds())],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:3000'],
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ]);

        $isPublishing = $values['action'] === 'publish';
        $existing = $mentor->grades()->where('student_id', $values['student_id'])->first();

        $grade = PklGrade::updateOrCreate(
            ['student_id' => $values['student_id'], 'mentor_id' => $mentor->id],
            [
                'score' => $values['score'],
                'feedback' => $values['feedback'] ?? null,
                'status' => $isPublishing ? 'Terbit' : 'Draf',
                'graded_at' => now()->toDateString(),
                'published_at' => $isPublishing ? ($existing?->published_at ?? now()) : null,
            ]
        );

        $student = $grade->student;

        if ($isPublishing && $student?->user_id) {
            Notification::notify(
                $student->user_id,
                'grade',
                'Nilai PKL diterbitkan',
                "{$mentor->typeLabel()} menerbitkan nilai PKL-mu: ".number_format((float) $grade->score, 2),
                route('student.portal.grade'),
            );
        }

        ActivityLog::record(
            $isPublishing ? 'Terbitkan Nilai' : 'Simpan Draf Nilai',
            "Nilai PKL ({$student?->nis})",
            "{$mentor->typeLabel()} {$request->user()->name} ".($isPublishing ? 'menerbitkan' : 'menyimpan draf')." nilai {$student?->name}",
        );

        return redirect()
            ->route('mentor.grades.index')
            ->with('success', $isPublishing ? 'Nilai berhasil diterbitkan.' : 'Draf nilai berhasil disimpan.');
    }
}
