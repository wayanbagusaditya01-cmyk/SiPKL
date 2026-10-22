<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Mentor;
use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['Sekolah', 'Industri'])],
        ]);

        $mentors = Mentor::query()
            ->with(['user:id,name,email', 'place:id,name'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('user', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                })->orWhere('nip', 'like', '%'.$search.'%')
                    ->orWhere('position', 'like', '%'.$search.'%');
            })
            ->when($filters['type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.mentors.index', compact('mentors', 'filters'));
    }

    public function create(): View
    {
        return view('coordinator.mentors.form', [
            'mentor' => null,
            'accounts' => $this->availableAccounts(),
            'places' => Place::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Mentor::create($this->validatedValues($request));

        return redirect()->route('coordinator.pembina.index')->with('success', 'Data Pembina berhasil ditambahkan.');
    }

    public function edit(Mentor $mentor): View
    {
        return view('coordinator.mentors.form', [
            'mentor' => $mentor->load('user'),
            'accounts' => $this->availableAccounts($mentor),
            'places' => Place::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Mentor $mentor): RedirectResponse
    {
        $mentor->update($this->validatedValues($request, $mentor));

        return redirect()->route('coordinator.pembina.index')->with('success', 'Data Pembina berhasil diperbarui.');
    }

    private function validatedValues(Request $request, ?Mentor $mentor = null): array
    {
        $accountRule = Rule::unique('mentors', 'user_id');

        if ($mentor) {
            $accountRule->ignore($mentor->id);
        }

        $isIndustry = $request->input('type') === 'Industri';
        $values = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'pembina')->where('status', true)),
                $accountRule,
            ],
            'type' => ['required', Rule::in(['Sekolah', 'Industri'])],
            'nip' => ['nullable', 'string', 'max:32'],
            'position' => [$isIndustry ? 'required' : 'nullable', 'string', 'max:120'],
            'phone' => [$isIndustry ? 'required' : 'nullable', 'string', 'max:32'],
            'place_id' => [
                $isIndustry ? 'required' : 'nullable',
                'integer',
                Rule::exists('places', 'id'),
            ],
        ]);

        $values['place_id'] = $isIndustry ? $values['place_id'] : null;
        $values['nip'] = $isIndustry ? null : ($values['nip'] ?? null);
        $values['position'] = $isIndustry ? $values['position'] : null;
        $values['phone'] = $isIndustry ? $values['phone'] : ($values['phone'] ?? null);

        return $values;
    }

    private function availableAccounts(?Mentor $mentor = null): Collection
    {
        return User::query()
            ->where('role', 'pembina')
            ->where('status', true)
            ->where(function (Builder $query) use ($mentor): void {
                $query->whereDoesntHave('mentor');

                if ($mentor) {
                    $query->orWhereKey($mentor->user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
