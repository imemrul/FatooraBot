<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'avatar_path',
        'password',
        'is_active',
        'is_super_admin',
        'has_seen_welcome_tour',
        'tutorial_score',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Check if user can access a given permission.
     * Accepts underscore format (manage_users) or dot format (manage.users).
     */
    public function canAccess(string $permission): bool
    {
        $normalized = str_replace('.', '_', $permission);

        return $this->can($normalized);
    }

    /**
     * Check if user can access any of the given permissions.
     */
    public function canAccessAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->canAccess($permission)) {
                return true;
            }
        }

        return false;
    }
}
