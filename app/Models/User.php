<?php
namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable, LogsActivity;

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
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, ['admin', 'supervisor', 'manager']);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function assignedClients()
    {
        return $this->hasMany(Client::class, 'assigned_user_id');
    }

    public function assignedLeadRequests()
    {
        return $this->hasMany(LeadRequest::class, 'assigned_user_id');
    }

    public function policies()
    {
        return $this->hasMany(Policy::class, 'agent_id');
    }

    public function reportedClaims()
    {
        return $this->hasMany(Claim::class, 'reported_by_id');
    }

    public function passwordResetRequests()
    {
        return $this->hasMany(PasswordResetRequest::class);
    }

    public function resolvedPasswordResetRequests()
    {
        return $this->hasMany(PasswordResetRequest::class, 'resolved_by_id');
    }
    
    public function scopeManageableBy(Builder $query, ?self $user): Builder
    {
        if (! $user instanceof self) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            return $query->where('role', 'manager');
        }

        return $query->whereRaw('1 = 0');
    }

    public function isManageableBy(?self $user): bool
    {
        if (! $user instanceof self) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupervisor()) {
            return $this->role === 'manager';
        }

        return false;
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
            'admin'      => 'Головний адміністратор',
            'supervisor' => 'Керівник відділу',
            'manager'    => 'Менеджер',
            default      => 'Невідомо',
        };
    }

    public function getActivityLogLabel(): string
    {
        return "{$this->name} ({$this->email})";
    }
}
