<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlaceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $places = Place::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('contact', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.places.index', compact('places', 'filters'));
    }

    public function create(): View
    {
        return view('coordinator.places.form', ['place' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $this->validatedValues($request);
        Place::create($values);

        return redirect()->route('coordinator.tempat.index')->with('success', 'Tempat PKL berhasil ditambahkan.');
    }

    public function edit(Place $place): View
    {
        return view('coordinator.places.form', compact('place'));
    }

    public function update(Request $request, Place $place): RedirectResponse
    {
        $place->update($this->validatedValues($request, $place));

        return redirect()->route('coordinator.tempat.index')->with('success', 'Tempat PKL berhasil diperbarui.');
    }

    private function validatedValues(Request $request, ?Place $place = null): array
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('places', 'name')->ignore($place?->id)],
            'address' => ['nullable', 'string', 'max:3000'],
            'contact' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $values['name'] = Str::squish($values['name']);

        return $values;
    }
}
