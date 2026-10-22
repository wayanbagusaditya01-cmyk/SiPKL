<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\PklPeriod;
use App\Models\Place;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $student = $this->studentFor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['Draf', 'Menunggu', 'Disetujui', 'Perlu Revisi'])],
        ]);
        $journals = $student->journals()
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('activity', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
        $journal = $request->filled('edit') ? $student->journals()->findOrFail($request->integer('edit')) : null;
        abort_if($journal && ! in_array($journal->status, ['Draf', 'Perlu Revisi'], true), 404);
        $period = $this->activePeriod($student);

        return view('students.journals.index', compact('student', 'journals', 'journal', 'filters', 'period'));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request);
        $period = $this->activePeriod($student);
        $values = $this->validatedValues($request, $period);

        $documentationPath = null;
        if ($request->hasFile('documentation_file')) {
            $documentationPath = $request->file('documentation_file')->store('journal_documentations', 'public');
        }

        $student->journals()->create([
            'activity_date' => $values['activity_date'],
            'title' => $values['title'],
            'activity' => $values['activity'],
            'documentation_url' => $values['documentation_url'] ?? null,
            'documentation_path' => $documentationPath,
            'status' => $values['action'] === 'draft' ? 'Draf' : 'Menunggu',
        ]);

        return redirect()->route('student.journals.index')->with('success', 'Jurnal berhasil disimpan.');
    }

    public function update(Request $request, JournalEntry $journal): RedirectResponse
    {
        $student = $this->studentFor($request);
        $ownedJournal = $student->journals()->findOrFail($journal->id);
        abort_unless(in_array($ownedJournal->status, ['Draf', 'Perlu Revisi'], true), 404);
        $values = $this->validatedValues($request, $this->activePeriod($student));

        $documentationPath = $ownedJournal->documentation_path;
        if ($request->hasFile('documentation_file')) {
            if ($ownedJournal->documentation_path) {
                Storage::disk('public')->delete($ownedJournal->documentation_path);
            }
            $documentationPath = $request->file('documentation_file')->store('journal_documentations', 'public');
        }

        $ownedJournal->update([
            'activity_date' => $values['activity_date'],
            'title' => $values['title'],
            'activity' => $values['activity'],
            'documentation_url' => $values['documentation_url'] ?? null,
            'documentation_path' => $documentationPath,
            'status' => $values['action'] === 'draft' ? $ownedJournal->status : 'Menunggu',
        ]);

        return redirect()->route('student.journals.index')->with('success', 'Jurnal berhasil diperbarui.');
    }

    public function destroy(Request $request, JournalEntry $journal): RedirectResponse
    {
        $student = $this->studentFor($request);
        $ownedJournal = $student->journals()->findOrFail($journal->id);
        abort_unless($ownedJournal->status === 'Draf', 403, 'Hanya jurnal berstatus Draf yang dapat dihapus.');

        if ($ownedJournal->documentation_path) {
            Storage::disk('public')->delete($ownedJournal->documentation_path);
        }

        $ownedJournal->delete();

        return redirect()->route('student.journals.index')->with('success', 'Draf jurnal berhasil dihapus.');
    }

    private function validatedValues(Request $request, ?PklPeriod $period): array
    {
        if (! $period) {
            throw ValidationException::withMessages([
                'activity_date' => 'Jurnal hanya dapat diisi setelah penempatan dan periode PKL aktif tersedia.',
            ]);
        }

        return $request->validate([
            'activity_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$period->start_date->format('Y-m-d'),
                'before_or_equal:'.$period->end_date->format('Y-m-d'),
            ],
            'title' => ['required', 'string', 'max:120'],
            'activity' => ['required', 'string', 'max:10000'],
            'documentation_url' => ['nullable', 'url:http,https', 'max:255'],
            'documentation_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'action' => ['required', Rule::in(['draft', 'send'])],
        ]);
    }

    private function studentFor(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->ensureStudent();
    }

    private function activePeriod(Student $student): ?PklPeriod
    {
        $placement = $student->placement()->with('period')->first();

        if ($placement && $placement->status === 'Aktif') {
            if ($placement->period && $placement->period->status === 'Aktif') {
                return $placement->period;
            }

            $activePeriod = PklPeriod::where('status', 'Aktif')->first();
            if ($activePeriod) {
                if (! $placement->period_id) {
                    $placement->update(['period_id' => $activePeriod->id]);
                }

                return $activePeriod;
            }
        }

        $activePeriod = PklPeriod::where('status', 'Aktif')->first();
        if ($activePeriod && ! $placement) {
            $firstPlace = Place::first();
            if ($firstPlace) {
                $student->placement()->create([
                    'place_id' => $firstPlace->id,
                    'period_id' => $activePeriod->id,
                    'status' => 'Aktif',
                    'placed_at' => now()->toDateString(),
                ]);

                return $activePeriod;
            }
        }

        return $placement?->period;
    }
}
