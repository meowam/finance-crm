<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_number',
        'country',
        'contact_email',
        'contact_phone',
        'website',
        'logo_path', 
    ];

    public function offers()
    {
        return $this->hasMany(InsuranceOffer::class, 'insurance_company_id');
    }
}
