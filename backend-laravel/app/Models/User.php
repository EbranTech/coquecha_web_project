<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'user_accounts';

    protected $fillable = ['person_id', 'role_id', 'username', 'password_hash', 'is_active'];

    protected $hidden = ['password_hash', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->person?->full_name ?? $this->username;
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function responsibleShifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'responsible_user_id');
    }

    public function managedSectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'sector_responsibles')->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'created_by_user_id');
    }

    public function serviceInterruptions(): HasMany
    {
        return $this->hasMany(ServiceInterruption::class, 'created_by_user_id');
    }
}
