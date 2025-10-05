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

    public function product()
    {
        return $this->belongsTo(InsuranceProduct::class, 'insurance_product_id');
    }
}
