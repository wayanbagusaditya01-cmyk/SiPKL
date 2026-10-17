<?php

namespace App\Models;

use Database\Factories\PklPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'academic_year', 'start_date', 'end_date', 'status'])]
class PklPeriod extends Model
{
    /** @use HasFactory<PklPeriodFactory> */
    use HasFactory;

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'period_id');
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
