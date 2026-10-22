<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MentorAttendanceController extends Controller
{
    use ResolvesMentor;

    public function index(Request $request): View
    {
        $mentor = $this->mentorFor($request);
        $studentIds = $mentor->guidedStudentIds();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['Hadir', 'Izin'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : 'nullable'],
        ]);

        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->with('student')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('student', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('attendance_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('attendance_date', '<=', $to))
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $presentCount = Attendance::whereIn('student_id', $studentIds)->where('status', 'Hadir')->count();
        $totalCount = Attendance::whereIn('student_id', $studentIds)->count();

        return view('mentor.attendance.index', compact('mentor', 'attendances', 'filters', 'presentCount', 'totalCount'));
    }
}
