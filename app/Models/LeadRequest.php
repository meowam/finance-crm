<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'type',
        'first_name',
        'last_name',
        'middle_name',
        'company_name',
        'phone',
        'email',
        'interest',
        'source',
        'status',
        'comment',
        'assigned_user_id',
        'converted_client_id',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function convertedClient()
    {
        return $this->belongsTo(Client::class, 'converted_client_id')->withTrashed();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->where('assigned_user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function isVisibleTo(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        return $user->isManager() && (int) $this->assigned_user_id === (int) $user->id;
    }

    public function resolveConvertedClient(): ?Client
    {
        if (blank($this->converted_client_id)) {
            return null;
        }

        if ($this->relationLoaded('convertedClient')) {
            return $this->convertedClient;
        }

        return $this->convertedClient()->first();
    }

    public function hasExistingClient(): bool
    {
        $client = $this->resolveConvertedClient();

        return $client instanceof Client && ! $client->trashed();
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            trim((string) $this->last_name),
            trim((string) $this->first_name),
            trim((string) $this->middle_name),
        ]);

        return $parts !== [] ? implode(' ', $parts) : '—';
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->type === 'company' && filled($this->company_name)) {
            return $this->company_name;
        }

        return $this->full_name !== '—' ? $this->full_name : ('Lead #' . $this->id);
    }

    public function getActivityLogLabelAttribute(): string
    {
        return $this->display_label;
    }

    public function getActivityLogLabel(): string
    {
        return $this->display_label;
    }
}