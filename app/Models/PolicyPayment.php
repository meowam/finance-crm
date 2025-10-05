<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id', 'due_date', 'paid_at', 'amount',
        'status', 'method', 'transaction_reference', 'notes'
    ];

    protected $dates = ['due_date', 'paid_at'];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
}
