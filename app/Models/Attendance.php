<?php

namespace App\Models;

use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'attendance_date', 'status', 'time_in', 'time_out', 'late_minutes', 'note', 'leave_photo_path',
    'check_in_latitude', 'check_in_longitude', 'check_in_accuracy', 'check_in_distance',
    'check_out_latitude', 'check_out_longitude', 'check_out_accuracy', 'check_out_distance',
])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Siswa sudah absen masuk dengan lokasi tetapi belum absen pulang.
     */
    public function awaitsCheckOut(): bool
    {
        return $this->status === 'Hadir' && $this->time_in !== null && $this->time_out === null;
    }

    /**
     * Jam masuk dalam format H:i (kolom TIME MySQL menyertakan detik).
     */
    protected function timeIn(): Attribute
    {
        return Attribute::get(fn (?string $value): ?string => $value ? substr($value, 0, 5) : null);
    }

    /**
     * Jam pulang dalam format H:i.
     */
    protected function timeOut(): Attribute
    {
        return Attribute::get(fn (?string $value): ?string => $value ? substr($value, 0, 5) : null);
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'late_minutes' => 'integer',
        ];
    }
}
