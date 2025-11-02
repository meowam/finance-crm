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

    protected static function booted(): void
    {
        static::creating(function (PolicyPayment $m) {
            if (! empty($m->transaction_reference)) {
                return;
            }

            do {
                $ref = 'TRX' . str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT);
            } while (self::where('transaction_reference', $ref)->exists());

            $m->transaction_reference = $ref;
        });
    }
}
