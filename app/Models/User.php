<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id', 'branch_id', 'name', 'phone',
        'email', 'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function extraPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_extra_permissions')
                    ->withPivot('granted_by', 'expires_at', 'reason', 'is_active');
    }

    // Role check helpers
    public function hasRole(string $role): bool
    {
        return $this->role?->role_name === $role;
    }

    public function hasPermission(string $permissionKey): bool
    {
        // Role permissions
        if ($this->role && $this->role->permissions->contains('permission_key', $permissionKey)) {
            return true;
        }
        // Extra permissions
        return $this->extraPermissions()
                    ->where('permission_key', $permissionKey)
                    ->where('is_active', true)
                    ->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
