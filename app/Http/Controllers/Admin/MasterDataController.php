<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(Request $request): View
    {
        SystemReference::ensureDefaults();

        $category = $request->query('category');

        $categories = SystemReference::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $references = SystemReference::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('code')
            ->get();

        $grouped = $references->groupBy('category');

        return view('admin.master_data.index', [
            'categories' => $categories,
            'grouped' => $grouped,
            'selectedCategory' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'category' => ['required', 'string', 'max:64'],
            'code' => ['required', 'string', 'max:64', 'alpha_dash'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category = trim($values['category']);
        $code = Str::lower(trim($values['code']));

        $exists = SystemReference::where('category', $category)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => "Kode '{$code}' sudah ada dalam kategori '{$category}'."])->withInput();
        }

        $reference = SystemReference::create([
            'category' => $category,
            'code' => $code,
            'name' => trim($values['name']),
            'description' => $values['description'] ? trim($values['description']) : null,
            'manager_role' => 'Admin',
            'is_system' => false,
        ]);

        ActivityLog::record(
            'Tambah Master Data',
            "Master Data ({$reference->category}: {$reference->name})",
            "Menambahkan master data baru kategori {$reference->category} dengan kode {$reference->code}",
            $request->user()
        );

        return redirect()->route('admin.master-data.index', ['category' => $category])
            ->with('success', "Master data '{$reference->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, SystemReference $reference): RedirectResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $oldName = $reference->name;
        $reference->update([
            'name' => trim($values['name']),
            'description' => $values['description'] ? trim($values['description']) : null,
        ]);

        ActivityLog::record(
            'Ubah Master Data',
            "Master Data ({$reference->category}: {$reference->code})",
            "Mengubah nama master data dari '{$oldName}' menjadi '{$reference->name}'",
            $request->user()
        );

        return redirect()->route('admin.master-data.index', ['category' => $reference->category])
            ->with('success', "Master data '{$reference->name}' berhasil diperbarui.");
    }

    public function destroy(Request $request, SystemReference $reference): RedirectResponse
    {
        if ($reference->is_system) {
            return back()->withErrors(['error' => 'Data referensi bawaan sistem tidak dapat dihapus.']);
        }

        $category = $reference->category;
        $name = $reference->name;
        $reference->delete();

        ActivityLog::record(
            'Hapus Master Data',
            "Master Data ({$category}: {$name})",
            "Menghapus master data {$name} dari kategori {$category}",
            $request->user()
        );

        return redirect()->route('admin.master-data.index', ['category' => $category])
            ->with('success', "Master data '{$name}' berhasil dihapus.");
    }
}
