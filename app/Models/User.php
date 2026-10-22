<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Peran akun yang menggunakan portal Pembina. "pembina" adalah peran lama
     * yang tipenya ditentukan oleh profil Mentor.
     */
    public const MENTOR_ROLES = ['pembina_sekolah', 'pembina_industri', 'pembina'];

    public function isMentor(): bool
    {
        return in_array($this->role, self::MENTOR_ROLES, true);
    }

    /**
     * Tipe profil Mentor yang wajib dimiliki akun ini, atau null bila bebas.
     */
    public function expectedMentorType(): ?string
    {
        return match ($this->role) {
            'pembina_sekolah' => Mentor::SCHOOL,
            'pembina_industri' => Mentor::INDUSTRY,
            default => null,
        };
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->orderByDesc('created_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function ensureStudent(): Student
    {
        $student = $this->student;

        if ($student) {
            return $student;
        }

        $existing = Student::whereNull('user_id')
            ->where(function ($query): void {
                $query->where('email', $this->email)
                    ->orWhere('name', $this->name);
            })
            ->first();

        if ($existing) {
            $existing->update(['user_id' => $this->id]);

            return $existing;
        }

        $maxNis = Student::max('nis');
        $nextNis = is_numeric($maxNis) ? (string) ((int) $maxNis + 1) : (string) (1000 + $this->id);
        while (Student::where('nis', $nextNis)->exists()) {
            $nextNis = (string) ((int) $nextNis + 1);
        }

        return $this->student()->create([
            'nis' => $nextNis,
            'name' => $this->name,
            'class' => 'XII RPL 1',
            'major' => 'Rekayasa Perangkat Lunak',
            'email' => $this->email,
        ]);
    }

    public function mentor(): HasOne
    {
        return $this->hasOne(Mentor::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderByDesc('created_at');
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->whereNull('read_at');
    }
}
