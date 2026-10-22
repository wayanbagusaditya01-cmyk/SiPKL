<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'actor_name', 'role', 'action', 'entity', 'detail', 'ip_address'])]
class ActivityLog extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $entity, ?string $detail = null, ?User $user = null, ?string $ip = null): self
    {
        $actor = $user ?? (auth()->check() ? auth()->user() : null);

        return self::create([
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Sistem',
            'role' => $actor?->role ?? 'admin',
            'action' => $action,
            'entity' => $entity,
            'detail' => $detail,
            'ip_address' => $ip ?? (app()->runningInConsole() ? '127.0.0.1' : request()->ip()),
        ]);
    }
}
