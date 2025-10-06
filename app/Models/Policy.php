<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_number',
        'client_id',
        'insurance_offer_id',
        'agent_id',
        'status',
        'effective_date',
        'expiration_date',
        'premium_amount',
        'coverage_amount',
        'payment_frequency',
        'commission_rate',
        'notes',
    ];

    protected $dates = [
        'effective_date',
        'expiration_date',
        'deleted_at',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function insuranceOffer()
    {
        return $this->belongsTo(InsuranceOffer::class, 'insurance_offer_id');
    }

    public function insuranceProduct()
    {
        return $this->hasOneThrough(
            InsuranceProduct::class,
            InsuranceOffer::class,
            'id', // foreign key у InsuranceOffer (primary)
            'id', // primary key у InsuranceProduct
            'insurance_offer_id', // foreign key у Policy
            'insurance_product_id' // foreign key у InsuranceOffer
        );
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function payments()
    {
        return $this->hasMany(PolicyPayment::class, 'policy_id');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class, 'policy_id');
    }
}