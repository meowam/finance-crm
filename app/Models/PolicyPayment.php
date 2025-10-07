<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'due_date',
        'paid_at',
        'amount',
        'status',
        'method',
        'transaction_reference',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime',
        'amount'   => 'decimal:2',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
}
