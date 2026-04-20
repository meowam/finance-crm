<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait LogsActivity
{
    protected static array $activitySnapshots = [];

    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            if (app()->runningInConsole()) {
                return;
            }

            $after = static::sanitizeActivityData($model->getAttributes());

            ActivityLog::createForModel(
                $model,
                'created',
                null,
                $after,
                static::buildActivityDescription($model, 'created')
            );
        });

        static::updating(function (Model $model) {
            if (app()->runningInConsole()) {
                return;
            }

            $dirty = $model->getDirty();
            unset($dirty['updated_at']);

            $before = [];
            $after = [];

            foreach (array_keys($dirty) as $key) {
                $before[$key] = $model->getOriginal($key);
                $after[$key] = $model->getAttribute($key);
            }

            static::$activitySnapshots[spl_object_id($model)] = [
                'before' => static::sanitizeActivityData($before),
                'after' => static::sanitizeActivityData($after),
            ];
        });

        static::updated(function (Model $model) {
            if (app()->runningInConsole()) {
                return;
            }

            $key = spl_object_id($model);
            $snapshot = static::$activitySnapshots[$key] ?? null;

            unset(static::$activitySnapshots[$key]);

            $before = $snapshot['before'] ?? [];
            $after = $snapshot['after'] ?? [];

            if ($before === [] && $after === []) {
                return;
            }

            ActivityLog::createForModel(
                $model,
                'updated',
                $before,
                $after,
                static::buildActivityDescription($model, 'updated')
            );
        });

        static::deleting(function (Model $model) {
            if (app()->runningInConsole()) {
                return;
            }

            static::$activitySnapshots[spl_object_id($model)] = [
                'before' => static::sanitizeActivityData($model->getOriginal()),
                'after' => null,
            ];
        });

        static::deleted(function (Model $model) {
            if (app()->runningInConsole()) {
                return;
            }

            $key = spl_object_id($model);
            $snapshot = static::$activitySnapshots[$key] ?? null;

            unset(static::$activitySnapshots[$key]);

            $before = $snapshot['before'] ?? static::sanitizeActivityData($model->getOriginal());

            ActivityLog::createForModel(
                $model,
                'deleted',
                $before,
                null,
                static::buildActivityDescription($model, 'deleted')
            );
        });
    }

    protected static function sanitizeActivityData(array $data): array
    {
        $ignored = [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($ignored as $key) {
            unset($data[$key]);
        }

        foreach ($data as $key => $value) {
            if (is_object($value) && method_exists($value, 'value')) {
                $data[$key] = $value->value;
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if (is_array($value)) {
                $data[$key] = static::normalizeArrayValues($value);
            }
        }

        return Arr::where($data, fn ($value) => ! is_null($value));
    }

    protected static function normalizeArrayValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_object($value) && method_exists($value, 'value')) {
                $data[$key] = $value->value;
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if (is_array($value)) {
                $data[$key] = static::normalizeArrayValues($value);
            }
        }

        return $data;
    }

    protected static function buildActivityDescription(Model $model, string $action): string
    {
        $label = method_exists($model, 'getActivityLogLabel')
            ? $model->getActivityLogLabel()
            : class_basename($model) . ' #' . $model->getKey();

        return match ($action) {
            'created' => "Створено: {$label}",
            'updated' => "Оновлено: {$label}",
            'deleted' => "Видалено: {$label}",
            default => "{$action}: {$label}",
        };
    }
}