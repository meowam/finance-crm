<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_product_id',
        'company_name',
        'offer_name',
        'price',
        'coverage_amount',
        'duration_days',
        'franchise',
        'is_active',
        'benefits',
        'conditions',
    ];

    public function insuranceProduct()
    {
        return $this->belongsTo(InsuranceProduct::class, 'insurance_product_id');
    }

    // Компанія, яка пропонує цей оффер
    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    // Поліси, створені за цим оффером
    public function policies()
    {
        return $this->hasMany(Policy::class, 'insurance_offer_id');
    }
}