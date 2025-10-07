<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, ['admin','supervisor','manager']);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function assignedClients()
    {
        return $this->hasMany(Client::class, 'assigned_user_id');
    }

    public function policies()
    {
        return $this->hasMany(Policy::class, 'agent_id');
    }

    public function reportedClaims()
    {
        return $this->hasMany(Claim::class, 'reported_by_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Головний адміністратор',
            'supervisor' => 'Керівник відділу',
            'manager' => 'Менеджер',
            default => 'Невідомо',
        };
    }
}
