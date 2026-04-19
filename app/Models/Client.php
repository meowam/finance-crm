<?php

namespace App\Models;

use App\Models\ClientContact;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

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
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
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

        if (filled($this->document_number)) {
            $chunks[] = $this->document_number;
        }

        if (filled($this->tax_id)) {
            $chunks[] = $this->tax_id;
        }

        return implode(' · ', array_filter($chunks, fn ($value) => filled($value)));
    }

    // public function activities()
    // {
    //     return $this->hasMany(Activity::class);
    // }
}