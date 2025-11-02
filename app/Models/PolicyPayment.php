<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'due_date',
        'initiated_at',
        'paid_at',
        'amount',
        'status',
        'method',
        'transaction_reference',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'initiated_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'method' => PaymentMethod::class,
    ];

    public function policy() { return $this->belongsTo(Policy::class); }

    protected static function booted(): void
    {
        static::creating(function (PolicyPayment $m) {
            if (blank($m->transaction_reference)) {
                do { $ref = 'TRX' . str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT); }
                while (self::where('transaction_reference', $ref)->exists());
                $m->transaction_reference = $ref;
            }
        });

        static::saving(function (PolicyPayment $m) {
            $method = $m->method instanceof PaymentMethod ? $m->method->value : $m->method;
            $status = $m->status instanceof PaymentStatus ? $m->status->value : $m->status;

            $allowed = match ($method) {
                'cash', 'card' => ['paid', 'canceled'],
                'transfer' => ['scheduled', 'paid', 'canceled', 'overdue'],
                'no_method' => ['draft', 'overdue', 'canceled'],
                default => [],
            };
            if (!in_array($status, $allowed, true)) {
                throw new \InvalidArgumentException('Invalid status for method');
            }

            if (blank($m->due_date)) {
                $m->due_date = now()->addDays(rand(5,7))->toDateString();
            }

            if (in_array($method, ['cash', 'card']) && $status === 'paid' && blank($m->paid_at)) {
                $m->paid_at = now();
            }

            if ($method === 'transfer' && blank($m->initiated_at)) {
                $m->initiated_at = now();
            }

            if (in_array($status, ['paid','scheduled'], true)) {
                $existsBlocking = self::query()
                    ->where('policy_id', $m->policy_id)
                    ->whereIn('status', ['paid','scheduled'])
                    ->when($m->exists, fn($q) => $q->where('id', '!=', $m->id))
                    ->exists();
                if ($existsBlocking) {
                    throw new \RuntimeException('Another blocking payment exists for this policy');
                }
            }
        });

        static::saved(function (PolicyPayment $m) {
            $policy = $m->policy;
            if (!$policy) return;

            if ($m->status === PaymentStatus::Paid) {
                if ($policy->expiration_date && now()->greaterThanOrEqualTo($policy->expiration_date)) {
                    $policy->status = PolicyStatus::Completed;
                } else {
                    if (!in_array($policy->status->value, [PolicyStatus::Completed->value, PolicyStatus::Canceled->value], true)) {
                        $policy->status = PolicyStatus::Active;
                    }
                }
                $policy->saveQuietly();
                return;
            }

            if ($m->status === PaymentStatus::Canceled) {
                if ($policy->payment_due_at && now()->isAfter($policy->payment_due_at) &&
                    !$policy->payments()->where('status', PaymentStatus::Paid->value)->exists()) {
                    $policy->status = PolicyStatus::Canceled;
                } else {
                    $policy->status = PolicyStatus::Draft;
                }
                $policy->saveQuietly();
                return;
            }

            if ($m->status === PaymentStatus::Overdue) {
                if (!$policy->payments()->where('status', PaymentStatus::Paid->value)->exists()) {
                    $policy->status = PolicyStatus::Canceled;
                    $policy->saveQuietly();
                }
                return;
            }

            if ($m->status === PaymentStatus::Scheduled) {
                $policy->status = PolicyStatus::Draft;
                $policy->saveQuietly();
            }
        });
    }
}
