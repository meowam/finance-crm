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
        'status'           => PolicyStatus::class,
        'effective_date'   => 'date',
        'expiration_date'  => 'date',
        'payment_due_at'   => 'date',
        'premium_amount'   => 'decimal:2',
        'coverage_amount'  => 'decimal:2',
        'commission_rate'  => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
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
                'amount'   => $model->premium_amount,
                'method'   => 'no_method',
                'status'   => 'draft',
                'due_date' => Carbon::parse($base)->addDays(7)->toDateString(),
            ]);

            $model->refresh()->recomputeStatus();
        });

        static::saved(function (Policy $model) {
            if ($model->wasChanged(['effective_date']) && filled($model->effective_date)) {
                $newDueDate = Carbon::parse($model->effective_date)->addDays(7)->toDateString();

                if (
                    blank($model->payment_due_at) ||
                    $model->payment_due_at?->toDateString() !== $newDueDate
                ) {
                    $model->forceFill([
                        'payment_due_at' => $newDueDate,
                    ])->saveQuietly();
                }
            }
        });
    }

    public function recomputeStatus(): void
    {
        $hasPaid = $this->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->exists();

        $newStatus = match (true) {
            $hasPaid && $this->expiration_date && now()->greaterThanOrEqualTo($this->expiration_date)
                => PolicyStatus::Completed,

            $hasPaid
                => PolicyStatus::Active,

            $this->payment_due_at && now()->isAfter($this->payment_due_at)
                => PolicyStatus::Canceled,

            default
                => PolicyStatus::Draft,
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