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

    protected $casts = [
        'effective_date'  => 'date',
        'expiration_date' => 'date',
        'premium_amount'  => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function insuranceOffer()
    {
        return $this->belongsTo(InsuranceOffer::class, 'insurance_offer_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function payments()
    {
        return $this->hasMany(PolicyPayment::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
