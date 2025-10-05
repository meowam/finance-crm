<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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

    // --- зв’язки ---
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

    // --- зручні методи перевірки ролі ---
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

    // --- форматування імені ролі для адмінки ---
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
