<?php

namespace App\Models;

use Database\Factories\PlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'place_id', 'period_id', 'school_mentor_id', 'industry_mentor_id', 'placed_at', 'status'])]
class Placement extends Model
{
    /** @use HasFactory<PlacementFactory> */
    use HasFactory;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PklPeriod::class, 'period_id');
    }

    public function schoolMentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class, 'school_mentor_id');
    }

    public function industryMentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class, 'industry_mentor_id');
    }

    protected function casts(): array
    {
        return [
            'placed_at' => 'date',
        ];
    }
}
