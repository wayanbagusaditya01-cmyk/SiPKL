<?php

namespace App\Models;

use Database\Factories\PlaceHolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['place_id', 'start_date', 'end_date', 'name'])]
class PlaceHoliday extends Model
{
    /** @use HasFactory<PlaceHolidayFactory> */
    use HasFactory;

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
