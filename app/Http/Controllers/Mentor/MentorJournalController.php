<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\JournalEntry;
use App\Models\Mentor;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MentorJournalController extends Controller
{
    use ResolvesMentor;

    public function index(Request $request): View
    {
        $mentor = $this->mentorFor($request);
        $studentIds = $mentor->guidedStudentIds();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['Menunggu', 'Disetujui', 'Perlu Revisi'])],
            'student' => ['nullable', 'integer'],
            'journal' => ['nullable', 'integer'],
        ]);

        $submittedJournals = fn () => JournalEntry::whereIn('student_id', $studentIds)->where('status', '!=', 'Draf');

        $journals = $submittedJournals()
            ->with('student')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('activity', 'like', '%'.$search.'%')
                        ->orWhereHas('student', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['student'] ?? null, fn (Builder $query, $studentId) => $query->where('student_id', (int) $studentId))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $selected = isset($filters['journal'])
            ? $submittedJournals()->with(['student', 'reviewer.user'])->findOrFail($filters['journal'])
            : $journals->firstWhere('status', 'Menunggu');

        $pendingCount = $submittedJournals()->where('status', 'Menunggu')->count();
        $students = Student::whereIn('id', $studentIds)->orderBy('name')->get(['id', 'name']);

        return view('mentor.journals.index', compact('mentor', 'journals', 'filters', 'selected', 'pendingCount', 'students'));
    }

    public function approve(Request $request, JournalEntry $journal): RedirectResponse
    {
        $mentor = $this->mentorFor($request);
        $this->authorizeJournal($mentor, $journal);

        $values = $request->validate([
            'mentor_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->review($request, $mentor, $journal, 'Disetujui', $values['mentor_note'] ?? null);

        return redirect()->route('mentor.journals.index')->with('success', 'Jurnal berhasil disetujui.');
    }

    public function revise(Request $request, JournalEntry $journal): RedirectResponse
    {
        $mentor = $this->mentorFor($request);
        $this->authorizeJournal($mentor, $journal);

        $values = $request->validate([
            'mentor_note' => ['required', 'string', 'max:3000'],
        ]);

        $this->review($request, $mentor, $journal, 'Perlu Revisi', $values['mentor_note']);

        return redirect()->route('mentor.journals.index')->with('success', 'Jurnal dikembalikan untuk revisi.');
    }

    private function review(Request $request, Mentor $mentor, JournalEntry $journal, string $status, ?string $note): void
    {
        $journal->update([
            'status' => $status,
            'mentor_note' => $note,
            'reviewed_by' => $mentor->id,
            'reviewed_at' => now(),
        ]);

        $reviewer = $request->user()->name;

        if ($journal->student?->user_id) {
            Notification::notify(
                $journal->student->user_id,
                'jurnal',
                $status === 'Disetujui' ? 'Jurnal disetujui' : 'Jurnal perlu revisi',
                $status === 'Disetujui'
                    ? "Jurnal \"{$journal->title}\" telah disetujui oleh {$reviewer}."
                    : "Jurnal \"{$journal->title}\" memerlukan revisi dari {$reviewer}. Catatan: {$note}",
                route('student.journals.index'),
            );
        }

        ActivityLog::record(
            $status === 'Disetujui' ? 'Setujui Jurnal' : 'Minta Revisi Jurnal',
            "Jurnal ({$journal->id})",
            "{$mentor->typeLabel()} {$reviewer} menandai jurnal \"{$journal->title}\" milik {$journal->student?->name} sebagai {$status}",
        );
    }

    private function authorizeJournal(Mentor $mentor, JournalEntry $journal): void
    {
        abort_unless($mentor->guides($journal->student_id), 403);
        abort_unless($journal->status === 'Menunggu', 403, 'Hanya jurnal berstatus Menunggu yang dapat ditinjau.');
    }
}
