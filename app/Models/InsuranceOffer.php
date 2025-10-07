<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_product_id',
        'insurance_company_id',
        'offer_name',
        'price',           
        'coverage_amount',
        'duration_months',
        'franchise',
        'benefits',
        'conditions',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'coverage_amount'  => 'decimal:2',
        'franchise'        => 'decimal:2',
        'duration_months'  => 'integer',
        'conditions'       => 'array',
    ];

    public function insuranceProduct()
    {
        return $this->belongsTo(InsuranceProduct::class, 'insurance_product_id');
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function policies()
    {
        return $this->hasMany(Policy::class, 'insurance_offer_id');
    }
}
