<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Policy extends Model
{
    use HasFactory;

    public static bool $suppressAutoDraft = false;

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
        'payment_due_at',
    ];

    protected $casts = [
        'status'           => PolicyStatus::class,
        'effective_date'   => 'date',
        'expiration_date'  => 'date',
        'payment_due_at'   => 'date',
        'premium_amount'   => 'decimal:2',
        'coverage_amount'  => 'decimal:2',
        'commission_rate'  => 'decimal:2',
    ];

    public function client()          { return $this->belongsTo(Client::class); }
    public function insuranceOffer()  { return $this->belongsTo(InsuranceOffer::class, 'insurance_offer_id'); }
    public function agent()           { return $this->belongsTo(User::class, 'agent_id'); }
    public function payments()        { return $this->hasMany(PolicyPayment::class); }
    public function latestPayment()   { return $this->hasOne(PolicyPayment::class)->latestOfMany(); }

    protected static function booted(): void
    {
        static::creating(function (Policy $m) {
            if (!filled($m->policy_number)) {
                do { $candidate = 'POL-' . Str::upper(Str::random(10)); }
                while (self::where('policy_number', $candidate)->exists());
                $m->policy_number = $candidate;
            }

            if (blank($m->status)) {
                $m->status = PolicyStatus::Draft->value; 
            }

            if (blank($m->payment_due_at)) {
                $base = $m->effective_date ?: now()->toDateString();
                $m->payment_due_at = \Illuminate\Support\Carbon::parse($base)->addDays(7);
            }
        });

        static::created(function (Policy $m) {
            if (self::$suppressAutoDraft) {
                return;
            }

            $m->payments()->create([
                'amount'   => $m->premium_amount,
                'method'   => 'no_method',
                'status'   => 'draft',
                'due_date' => now()->addDays(rand(5, 7))->toDateString(),
            ]);
        });
    }

    public function recomputeStatus(): void
    {
        $hasPaid = $this->payments()->where('status', PaymentStatus::Paid->value)->exists();

        if ($hasPaid) {
            if ($this->expiration_date && now()->greaterThanOrEqualTo($this->expiration_date)) {
                $this->status = PolicyStatus::Completed;
            } else {
                if (!in_array($this->status->value, [PolicyStatus::Completed->value, PolicyStatus::Canceled->value], true)) {
                    $this->status = PolicyStatus::Active;
                }
            }
        } else {
            if ($this->payment_due_at && now()->isAfter($this->payment_due_at)) {
                $this->status = PolicyStatus::Canceled;
            } else {
                $this->status = PolicyStatus::Draft;
            }
        }

        $this->saveQuietly();
    }
}
