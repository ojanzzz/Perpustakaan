<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['institution_id', 'name', 'email', 'password', 'role', 'admin_level', 'status', 'last_login_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_enabled_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $role = $user->role instanceof UserRole
                ? $user->role
                : UserRole::tryFrom((string) $user->role);
            $level = $user->admin_level instanceof AdminLevel
                ? $user->admin_level
                : ($user->admin_level ? AdminLevel::tryFrom((string) $user->admin_level) : null);

            if ($role === null) {
                throw ValidationException::withMessages(['role' => 'Peran pengguna tidak valid.']);
            }

            if ($role === UserRole::Admin && $level === null) {
                throw ValidationException::withMessages([
                    'admin_level' => 'Level admin wajib diisi untuk pengguna admin.',
                ]);
            }

            if ($role !== UserRole::Admin && $level !== null) {
                throw ValidationException::withMessages([
                    'admin_level' => 'Level admin hanya dapat digunakan oleh pengguna admin.',
                ]);
            }
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('allowed')
            ->withTimestamps();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')->withTimestamps();
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function personalCollections(): HasMany
    {
        return $this->hasMany(PersonalCollection::class);
    }

    public function subscribedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_subscriptions')->withTimestamps();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'admin_level' => AdminLevel::class,
            'status' => AccountStatus::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_enabled_at' => 'datetime',
        ];
    }
}
