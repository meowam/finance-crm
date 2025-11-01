<?php
namespace App\Models;

use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number', 'policy_id', 'reported_by_id', 'status',
        'reported_at', 'loss_occurred_at', 'loss_location', 'cause',
        'amount_claimed', 'amount_reserve', 'amount_paid', 'description', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
    public function reportedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reported_by_id');
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function notes()
    {
        return $this->hasMany(ClaimNote::class);
    }
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->claim_number)) {
                do {
                    $number = 'CLM-' . strtoupper(Str::random(12));
                } while (self::where('claim_number', $number)->exists());

                $model->claim_number = $number;
            }

            if (empty($model->reported_at)) {
                $model->reported_at = now();
            }
            
            if (is_null($model->amount_paid)) {
                $model->amount_paid = 0.00;
            }
        });
    }
}
