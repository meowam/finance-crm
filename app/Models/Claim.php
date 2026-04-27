<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Claim extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'claim_number',
        'policy_id',
        'reported_by_id',
        'status',
        'reported_at',
        'loss_occurred_at',
        'loss_location',
        'cause',
        'amount_claimed',
        'amount_reserve',
        'amount_paid',
        'description',
        'metadata',
    ];

    protected $casts = [
        'status' => ClaimStatus::class,
        'reported_at' => 'datetime',
        'loss_occurred_at' => 'date',
        'amount_claimed' => 'decimal:2',
        'amount_reserve' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function notes()
    {
        return $this->hasMany(ClaimNote::class);
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

    public function getActivityLogLabel(): string
    {
        return $this->claim_number ?: 'Заява #' . $this->id;
    }
}