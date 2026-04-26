<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\ClaimNote;
use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\PasswordResetRequest;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('activity_logs')->delete();

        $actors = User::query()
            ->where('is_active', true)
            ->get();

        if ($actors->isEmpty()) {
            return;
        }

        $subjects = collect()
            ->merge($this->subjectItems(Client::query()->limit(12)->get(), 'Клієнт'))
            ->merge($this->subjectItems(LeadRequest::query()->limit(12)->get(), 'Вхідна заявка'))
            ->merge($this->subjectItems(Policy::query()->limit(12)->get(), 'Поліс'))
            ->merge($this->subjectItems(PolicyPayment::query()->limit(12)->get(), 'Оплата'))
            ->merge($this->subjectItems(Claim::query()->limit(12)->get(), 'Страховий випадок'))
            ->merge($this->subjectItems(ClaimNote::query()->limit(12)->get(), 'Нотатка до заявки'))
            ->merge($this->subjectItems(PasswordResetRequest::query()->limit(8)->get(), 'Запит на зміну пароля'))
            ->values();

        if ($subjects->isEmpty()) {
            return;
        }

        $actions = [
            'created',
            'updated',
            'updated',
            'updated',
            'deleted',
        ];

        for ($i = 0; $i < 80; $i++) {
            /** @var User $actor */
            $actor = $actors->random();

            $subject = $subjects->random();
            $action = $actions[array_rand($actions)];

            $createdAt = Carbon::instance(fake('uk_UA')->dateTimeBetween(now()->subDays(20), now()));

            ActivityLog::query()->create([
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_role' => $actor->role,
                'action' => $action,
                'subject_type' => $subject['type'],
                'subject_id' => $subject['id'],
                'subject_type_label' => $subject['type_label'],
                'subject_label' => $subject['label'],
                'description' => $this->description($action, $subject['type_label'], $subject['label']),
                'before' => $this->beforePayload($action, $subject['type_label']),
                'after' => $this->afterPayload($action, $subject['type_label']),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command?->info('Activity logs: ' . ActivityLog::query()->count());
    }

    protected function subjectItems(Collection $models, string $typeLabel): Collection
    {
        return $models->map(function (Model $model) use ($typeLabel) {
            return [
                'type' => $model::class,
                'id' => $model->getKey(),
                'type_label' => $typeLabel,
                'label' => $this->subjectLabel($model),
            ];
        });
    }

    protected function subjectLabel(Model $model): string
    {
        if (method_exists($model, 'getActivityLogLabel')) {
            return $model->getActivityLogLabel();
        }

        if ($model instanceof LeadRequest) {
            return $model->display_label;
        }

        if ($model instanceof PasswordResetRequest) {
            return 'Запит #' . $model->id . ' — ' . ($model->user?->name ?? 'Користувач');
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    protected function description(string $action, string $typeLabel, string $subjectLabel): string
    {
        $actionLabel = match ($action) {
            'created' => 'створено',
            'updated' => 'оновлено',
            'deleted' => 'видалено',
            default => 'змінено',
        };

        return "{$typeLabel} «{$subjectLabel}» {$actionLabel}.";
    }

    protected function beforePayload(string $action, string $typeLabel): ?array
    {
        if ($action === 'created') {
            return null;
        }

        return match ($typeLabel) {
            'Клієнт' => [
                'status' => 'lead',
                'preferred_contact_method' => 'phone',
            ],
            'Вхідна заявка' => [
                'status' => 'new',
                'source' => 'landing',
            ],
            'Поліс' => [
                'status' => 'draft',
                'payment_due_at' => now()->subDays(2)->toDateString(),
            ],
            'Оплата' => [
                'status' => 'scheduled',
                'method' => 'transfer',
            ],
            'Страховий випадок' => [
                'status' => 'на розгляді',
                'amount_paid' => 0,
            ],
            'Нотатка до заявки' => [
                'visibility' => 'зовнішня',
            ],
            'Запит на зміну пароля' => [
                'status' => 'pending',
            ],
            default => [
                'status' => 'old',
            ],
        };
    }

    protected function afterPayload(string $action, string $typeLabel): ?array
    {
        if ($action === 'deleted') {
            return null;
        }

        return match ($typeLabel) {
            'Клієнт' => [
                'status' => 'active',
                'preferred_contact_method' => 'email',
            ],
            'Вхідна заявка' => [
                'status' => 'in_progress',
                'source' => 'landing',
            ],
            'Поліс' => [
                'status' => 'active',
                'payment_due_at' => now()->addDays(7)->toDateString(),
            ],
            'Оплата' => [
                'status' => 'paid',
                'method' => 'transfer',
            ],
            'Страховий випадок' => [
                'status' => 'схвалено',
                'amount_paid' => 5000,
            ],
            'Нотатка до заявки' => [
                'visibility' => 'внутрішня',
            ],
            'Запит на зміну пароля' => [
                'status' => 'resolved',
            ],
            default => [
                'status' => 'new',
            ],
        };
    }
}