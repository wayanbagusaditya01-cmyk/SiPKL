<?php

namespace App\Models;

use Database\Factories\MentorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'place_id', 'type', 'nip', 'position', 'phone'])]
class Mentor extends Model
{
    /** @use HasFactory<MentorFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function schoolPlacements(): HasMany
    {
        return $this->hasMany(Placement::class, 'school_mentor_id');
    }

    public function industryPlacements(): HasMany
    {
        return $this->hasMany(Placement::class, 'industry_mentor_id');
    }
}
