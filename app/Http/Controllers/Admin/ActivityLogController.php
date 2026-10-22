<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:32'],
            'action' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = ActivityLog::query()->with('user');

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('actor_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('entity', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();

        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action')->all();
        $roles = config('preview.roles');

        return view('admin.logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions,
            'roles' => $roles,
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', 30);

        if ($days <= 0) {
            $deletedCount = ActivityLog::count();
            ActivityLog::truncate();
            $msg = "Seluruh log aktivitas ({$deletedCount} rekaman) telah dibersihkan.";
        } else {
            $cutoff = now()->subDays($days);
            $deletedCount = ActivityLog::where('created_at', '<', $cutoff)->delete();
            $msg = "Log aktivitas yang lebih lama dari {$days} hari ({$deletedCount} rekaman) berhasil dibersihkan.";
        }

        ActivityLog::record(
            'Bersihkan Log',
            'Log Aktivitas',
            $msg,
            $request->user()
        );

        return redirect()->route('admin.logs.index')->with('success', $msg);
    }
}
