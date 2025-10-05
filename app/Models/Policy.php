<?php

namespace App\Models;

use App\Models\Client;
use App\Models\InsuranceProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_number', 'client_id', 'insurance_product_id', 'agent_id',
        'status', 'effective_date', 'expiration_date', 'premium_amount',
        'coverage_amount', 'payment_frequency', 'commission_rate', 'notes'
    ];

    protected $dates = ['effective_date', 'expiration_date', 'deleted_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(InsuranceProduct::class, 'insurance_product_id');
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
