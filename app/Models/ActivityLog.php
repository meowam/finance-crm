<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'action',
        'subject_type',
        'subject_id',
        'subject_type_label',
        'subject_label',
        'description',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('actor_role', 'manager')
                    ->orWhere('actor_id', $user->id);
            });
        }

        if ($user->isManager()) {
            return $query->where('actor_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Створення',
            'updated' => 'Оновлення',
            'deleted' => 'Видалення',
            default => ucfirst((string) $this->action),
        };
    }

    public static function subjectTypeLabelFor(Model $model): string
    {
        return match ($model::class) {
            User::class => 'Користувач',
            Client::class => 'Клієнт',
            Policy::class => 'Поліс',
            PolicyPayment::class => 'Оплата',
            Claim::class => 'Страховий випадок',
            ClaimNote::class => 'Нотатка до заявки',
            default => class_basename($model),
        };
    }

    public static function createForModel(
        Model $model,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $description = null,
    ): void {
        /** @var User|null $actor */
        $actor = auth()->user();

        static::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $actor?->role,
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'subject_type_label' => static::subjectTypeLabelFor($model),
            'subject_label' => method_exists($model, 'getActivityLogLabel')
                ? $model->getActivityLogLabel()
                : class_basename($model) . ' #' . $model->getKey(),
            'description' => $description,
            'before' => $before,
            'after' => $after,
        ]);
    }
}