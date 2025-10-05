<?php

namespace App\Models;

use App\Models\Activity;
use App\Models\ClientContact;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
    'type', 'status', 'first_name', 'last_name', 'middle_name',
    'company_name', 'primary_email', 'primary_phone', 'document_number',
    'tax_id', 'date_of_birth', 'preferred_contact_method',
    'city', 'address_line', 'source', 'assigned_user_id', 'notes'
];


    protected $dates = ['date_of_birth', 'deleted_at'];

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function policies()
    {
        return $this->hasMany(Policy::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
