<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\PklPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['Aktif', 'Selesai', 'Menunggu'])],
        ]);

        $periods = PklPeriod::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('academic_year', 'like', '%'.$search.'%');
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->orderByDesc('start_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.periods.index', compact('periods', 'filters'));
    }

    public function create(): View
    {
        return view('coordinator.periods.form', ['period' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        PklPeriod::create($this->validatedValues($request));

        return redirect()->route('coordinator.periode.index')->with('success', 'Periode PKL berhasil ditambahkan.');
    }

    public function edit(PklPeriod $period): View
    {
        return view('coordinator.periods.form', compact('period'));
    }

    public function update(Request $request, PklPeriod $period): RedirectResponse
    {
        $period->update($this->validatedValues($request, $period));

        return redirect()->route('coordinator.periode.index')->with('success', 'Periode PKL berhasil diperbarui.');
    }

    private function validatedValues(Request $request, ?PklPeriod $period = null): array
    {
        $nameRule = Rule::unique('pkl_periods', 'name');

        if ($period) {
            $nameRule->ignore($period->id);
        }

        $values = $request->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
            'academic_year' => ['required', 'regex:/^\\d{4}\\/\\d{4}$/'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'status' => ['required', Rule::in(['Aktif', 'Selesai', 'Menunggu'])],
        ]);

        $values['name'] = Str::squish($values['name']);

        return $values;
    }
}
