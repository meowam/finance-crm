<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'type',
        'status',
        'first_name',
        'last_name',
        'middle_name',
        'company_name',
        'primary_email',
        'primary_phone',
        'document_number',
        'tax_id',
        'date_of_birth',
        'preferred_contact_method',
        'city',
        'address_line',
        'source',
        'assigned_user_id',
        'notes',
    ];

    protected $dates = ['date_of_birth', 'deleted_at'];

    protected $appends = [
        'display_label',
        'full_name',
    ];

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function policies()
    {
        return $this->hasMany(Policy::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function convertedLeadRequests()
    {
        return $this->hasMany(LeadRequest::class, 'converted_client_id');
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

    public function hasDeletionHistory(): bool
    {
        return $this->policies()->exists()
            || $this->contacts()->exists()
            || $this->convertedLeadRequests()->exists();
    }

    public function archiveOrDelete(): bool|null
    {
        if ($this->hasDeletionHistory()) {
            if ($this->status !== 'archived') {
                $this->forceFill([
                    'status' => 'archived',
                ])->saveQuietly();
            }

            return $this->delete();
        }

        return $this->forceDelete();
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
        $chunks = [];

        if ($this->type === 'company' && filled($this->company_name)) {
            $chunks[] = trim((string) $this->company_name);

            $contactName = $this->full_name !== '—' ? $this->full_name : null;
            if ($contactName) {
                $chunks[] = 'контакт: ' . $contactName;
            }
        } else {
            $chunks[] = $this->full_name;
        }

        if (filled($this->primary_phone)) {
            $chunks[] = $this->primary_phone;
        }

        if (filled($this->primary_email)) {
            $chunks[] = $this->primary_email;
        }

        if (filled($this->tax_id)) {
            $chunks[] = $this->tax_id;
        }

        return implode(' · ', array_filter($chunks, fn ($value) => filled($value)));
    }

    public function getActivityLogLabel(): string
    {
        if ($this->type === 'company' && filled($this->company_name)) {
            return (string) $this->company_name;
        }

        return $this->full_name !== '—' ? $this->full_name : 'Клієнт #' . $this->id;
    }
}