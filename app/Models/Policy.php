<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Policy extends Model
{
    use HasFactory, LogsActivity;

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
        'status' => PolicyStatus::class,
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'payment_due_at' => 'date',
        'premium_amount' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function insuranceOffer()
    {
        return $this->belongsTo(InsuranceOffer::class, 'insurance_offer_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function payments()
    {
        return $this->hasMany(PolicyPayment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(PolicyPayment::class)->latestOfMany();
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
            return $query->where('agent_id', $user->id);
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

        return $user->isManager() && (int) $this->agent_id === (int) $user->id;
    }

    public function hasStarted(): bool
    {
        if (! $this->effective_date instanceof Carbon) {
            return false;
        }

        return now()->startOfDay()->greaterThanOrEqualTo(
            $this->effective_date->copy()->startOfDay()
        );
    }

    public function isEditableBeforeStart(): bool
    {
        $status = $this->status instanceof PolicyStatus
            ? $this->status->value
            : (string) $this->status;

        if ($status === PolicyStatus::Canceled->value) {
            return false;
        }

        if (! $this->effective_date instanceof Carbon) {
            return false;
        }

        return now()->startOfDay()->lt($this->effective_date->copy()->startOfDay());
    }

    public function syncPendingPaymentDueDatesFromEffectiveDate(): void
    {
        if (! $this->effective_date instanceof Carbon) {
            return;
        }

        $newDueDate = $this->effective_date->copy()->addDays(7)->toDateString();

        $this->payments()
            ->whereIn('status', [
                PaymentStatus::Draft->value,
                PaymentStatus::Scheduled->value,
                PaymentStatus::Overdue->value,
            ])
            ->update([
                'due_date' => $newDueDate,
            ]);
    }

    public function markCanceledWithPaymentSync(): void
    {
        $this->payments()->get()->each(function (PolicyPayment $payment): void {
            $status = $payment->status instanceof PaymentStatus
                ? $payment->status->value
                : (string) $payment->status;

            if ($status === PaymentStatus::Paid->value) {
                $payment->forceFill([
                    'status' => PaymentStatus::Refunded->value,
                ])->saveQuietly();

                return;
            }

            if (in_array($status, [
                PaymentStatus::Draft->value,
                PaymentStatus::Scheduled->value,
                PaymentStatus::Overdue->value,
            ], true)) {
                $payment->forceFill([
                    'status' => PaymentStatus::Canceled->value,
                ])->saveQuietly();
            }
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Policy $model) {
            if (! filled($model->policy_number)) {
                do {
                    $candidate = 'POL-' . Str::upper(Str::random(10));
                } while (self::where('policy_number', $candidate)->exists());

                $model->policy_number = $candidate;
            }

            if (blank($model->status)) {
                $model->status = PolicyStatus::Draft->value;
            }

            if (blank($model->payment_due_at)) {
                $base = $model->effective_date ?: now()->toDateString();
                $model->payment_due_at = Carbon::parse($base)->addDays(7);
            }
        });

        static::created(function (Policy $model) {
            if (self::$suppressAutoDraft) {
                return;
            }

            $base = $model->effective_date ?: now()->toDateString();

            $model->payments()->create([
                'amount' => $model->premium_amount,
                'method' => 'no_method',
                'status' => 'draft',
                'due_date' => Carbon::parse($base)->addDays(7)->toDateString(),
            ]);

            $model->refresh()->recomputeStatus();
        });

        static::saved(function (Policy $model) {
            $shouldRefresh = false;

            if ($model->wasChanged(['effective_date']) && filled($model->effective_date)) {
                $newDueDate = Carbon::parse($model->effective_date)->addDays(7)->toDateString();
                $currentDueDate = $model->payment_due_at?->toDateString();

                if (blank($model->payment_due_at) || $currentDueDate !== $newDueDate) {
                    $model->forceFill([
                        'payment_due_at' => $newDueDate,
                    ])->saveQuietly();

                    $shouldRefresh = true;
                }

                $model->syncPendingPaymentDueDatesFromEffectiveDate();
            }

            if ($shouldRefresh || $model->wasChanged(['effective_date', 'expiration_date', 'payment_due_at'])) {
                $model->refresh()->recomputeStatus();
            }
        });
    }

    public function recomputeStatus(): void
    {
        $currentStatus = $this->status instanceof PolicyStatus
            ? $this->status->value
            : (string) $this->status;

        if ($currentStatus === PolicyStatus::Canceled->value) {
            return;
        }

        $today = now()->startOfDay();

        $hasPaidPayment = $this->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->exists();

        $isExpired = $this->expiration_date instanceof Carbon
            ? $today->greaterThan($this->expiration_date->copy()->startOfDay())
            : false;

        $isPaymentOverdue = $this->payment_due_at instanceof Carbon
            ? $today->greaterThan($this->payment_due_at->copy()->startOfDay())
            : false;

        $newStatus = match (true) {
            $hasPaidPayment && $isExpired => PolicyStatus::Completed,
            $hasPaidPayment => PolicyStatus::Active,
            $isPaymentOverdue => PolicyStatus::Canceled,
            default => PolicyStatus::Draft,
        };

        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->saveQuietly();
        }
    }

    public function getActivityLogLabel(): string
    {
        return $this->policy_number ?: 'Поліс #' . $this->id;
    }
}