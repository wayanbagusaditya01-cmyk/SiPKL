<?php

namespace App\Models;

use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'contact', 'email', 'latitude', 'longitude'])]
class Place extends Model
{
    /** @use HasFactory<PlaceFactory> */
    use HasFactory;

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
