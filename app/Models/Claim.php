<?php

namespace App\Models;

use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number', 'policy_id', 'reported_by_id', 'status',
        'reported_at', 'loss_occurred_at', 'loss_location', 'cause',
        'amount_claimed', 'amount_reserve', 'amount_paid', 'description', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function notes()
    {
        return $this->hasMany(ClaimNote::class);
    }
}
