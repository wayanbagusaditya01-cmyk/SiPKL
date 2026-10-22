<?php

namespace App\Models;

use Database\Factories\PklGradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'mentor_id', 'score', 'feedback', 'status', 'graded_at', 'published_at'])]
class PklGrade extends Model
{
    /** @use HasFactory<PklGradeFactory> */
    use HasFactory;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
