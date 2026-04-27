<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PolicyPayment extends Model
{
    use HasFactory, LogsActivity;

    public const ACTIVE_STATUSES = [
        PaymentStatus::Paid->value,
        PaymentStatus::Scheduled->value,
    ];

    public const CANCEL_WHEN_POLICY_IS_PAID_STATUSES = [
        PaymentStatus::Draft->value,
        PaymentStatus::Scheduled->value,
        PaymentStatus::Overdue->value,
    ];

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
        'due_date'     => 'date',
        'initiated_at' => 'datetime',
        'paid_at'      => 'datetime',
        'amount'       => 'decimal:2',
        'status'       => PaymentStatus::class,
        'method'       => PaymentMethod::class,
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->whereHas('policy', function (Builder $policyQuery) use ($user) {
                $policyQuery->where('agent_id', $user->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function isVisibleTo(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return (int) optional($this->policy)->agent_id === (int) $user->id;
    }

    protected static function normalizeMethod(PolicyPayment $model): ?string
    {
        return $model->method instanceof PaymentMethod
            ? $model->method->value
            : $model->method;
    }

    protected static function normalizeStatus(PolicyPayment $model): ?string
    {
        return $model->status instanceof PaymentStatus
            ? $model->status->value
            : $model->status;
    }

    protected static function allowedStatusesForMethod(?string $method): array
    {
        return match ($method) {
            'cash', 'card' => ['paid', 'canceled', 'refunded'],
            'transfer'  => ['scheduled', 'paid', 'canceled', 'overdue', 'refunded'],
            'no_method' => ['draft', 'overdue', 'canceled'],
            default     => [],
        };
    }

    protected static function hasAnotherActivePayment(int $policyId, ?int $ignorePaymentId = null): bool
    {
        return self::query()
            ->where('policy_id', $policyId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->when($ignorePaymentId, fn (Builder $query) => $query->where('id', '!=', $ignorePaymentId))
            ->exists();
    }

    protected static function hasAnotherPaidPayment(int $policyId, ?int $ignorePaymentId = null): bool
    {
        return self::query()
            ->where('policy_id', $policyId)
            ->where('status', PaymentStatus::Paid->value)
            ->when($ignorePaymentId, fn (Builder $query) => $query->where('id', '!=', $ignorePaymentId))
            ->exists();
    }

    protected static function cancelOtherUnfinishedPaymentsForPolicy(int $policyId, int $paidPaymentId): void
    {
        self::query()
            ->where('policy_id', $policyId)
            ->where('id', '!=', $paidPaymentId)
            ->whereIn('status', self::CANCEL_WHEN_POLICY_IS_PAID_STATUSES)
            ->update([
                'status' => PaymentStatus::Canceled->value,
                'updated_at' => now(),
            ]);
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
            $method = self::normalizeMethod($model);
            $status = self::normalizeStatus($model);

            if (
                filled($model->policy_id)
                && $status === PaymentStatus::Draft->value
                && self::hasAnotherPaidPayment((int) $model->policy_id, $model->exists ? (int) $model->id : null)
            ) {
                $model->status = PaymentStatus::Canceled->value;
                $status = PaymentStatus::Canceled->value;
            }

            $allowed = self::allowedStatusesForMethod($method);

            if (! in_array($status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Обраний статус не відповідає вибраному методу оплати.',
                ]);
            }

            if (blank($model->due_date)) {
                $base            = $model->policy?->effective_date ?: now()->toDateString();
                $model->due_date = Carbon::parse($base)->addDays(7)->toDateString();
            }

            if (in_array($method, ['cash', 'card'], true) && $status === PaymentStatus::Paid->value && blank($model->paid_at)) {
                $model->paid_at = now();
            }

            if ($method === 'transfer' && blank($model->initiated_at) && in_array($status, [PaymentStatus::Scheduled->value, PaymentStatus::Paid->value], true)) {
                $model->initiated_at = now();
            }

            if (
                filled($model->policy_id) &&
                in_array($status, self::ACTIVE_STATUSES, true) &&
                self::hasAnotherActivePayment((int) $model->policy_id, $model->exists ? (int) $model->id : null)
            ) {
                throw ValidationException::withMessages([
                    'policy_id' => 'Для цього поліса вже існує активний платіж зі статусом «сплачено» або «заплановано». Спочатку завершіть або скасуйте поточний активний платіж.',
                ]);
            }
        });

        static::saved(function (PolicyPayment $model) {
            $status = self::normalizeStatus($model);

            if ($status === PaymentStatus::Paid->value && filled($model->policy_id)) {
                self::cancelOtherUnfinishedPaymentsForPolicy(
                    (int) $model->policy_id,
                    (int) $model->id
                );
            }

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
        $policyNumber = $this->policy?->policy_number ?: ('поліс #' . $this->policy_id);

        return "Оплата {$policyNumber}";
    }
}