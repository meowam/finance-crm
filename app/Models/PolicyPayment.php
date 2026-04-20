<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PolicyPayment extends Model
{
    use HasFactory, LogsActivity;

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

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PolicyPayment $model) {
            if (blank($model->transaction_reference)) {
                do {
                    $ref = 'TRX' . str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT);
                } while (self::where('transaction_reference', $ref)->exists());

                $model->transaction_reference = $ref;
            }
        });

        static::saving(function (PolicyPayment $model) {
            $method = $model->method instanceof PaymentMethod ? $model->method->value : $model->method;
            $status = $model->status instanceof PaymentStatus ? $model->status->value : $model->status;

            $allowed = match ($method) {
                'cash', 'card' => ['paid', 'canceled'],
                'transfer'     => ['scheduled', 'paid', 'canceled', 'overdue'],
                'no_method'    => ['draft', 'overdue', 'canceled'],
                default        => [],
            };

            if (! in_array($status, $allowed, true)) {
                throw new \InvalidArgumentException('Invalid status for method');
            }

            if (blank($model->due_date)) {
                $base = $model->policy?->effective_date ?: now()->toDateString();
                $model->due_date = Carbon::parse($base)->addDays(7)->toDateString();
            }

            if (in_array($method, ['cash', 'card'], true) && $status === 'paid' && blank($model->paid_at)) {
                $model->paid_at = now();
            }

            if ($method === 'transfer' && blank($model->initiated_at)) {
                $model->initiated_at = now();
            }

            if (in_array($status, ['paid', 'scheduled'], true)) {
                $existsBlocking = self::query()
                    ->where('policy_id', $model->policy_id)
                    ->whereIn('status', ['paid', 'scheduled'])
                    ->when($model->exists, fn ($q) => $q->where('id', '!=', $model->id))
                    ->exists();

                if ($existsBlocking) {
                    throw new \RuntimeException('Another blocking payment exists for this policy');
                }
            }
        });

        static::saved(function (PolicyPayment $model) {
            $policy = $model->policy;

            if (! $policy) {
                return;
            }

            $policy->refresh()->recomputeStatus();
        });

        static::deleted(function (PolicyPayment $model) {
            $policy = $model->policy;

            if (! $policy) {
                return;
            }

            $policy->refresh()->recomputeStatus();
        });
    }

    public function getActivityLogLabel(): string
    {
        $policyNumber = $this->policy?->policy_number ?: ('policy #' . $this->policy_id);

        return "Оплата {$policyNumber}";
    }
}