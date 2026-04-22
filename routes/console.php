<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\PolicyPayment;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:mark-overdue', function () {
    $today = now()->startOfDay()->toDateString();
    $updated = 0;

    PolicyPayment::query()
        ->whereDate('due_date', '<', $today)
        ->where(function ($query) {
            $query
                ->where(function ($subQuery) {
                    $subQuery
                        ->where('method', PaymentMethod::Transfer->value)
                        ->whereIn('status', [
                            PaymentStatus::Scheduled->value,
                            PaymentStatus::Draft->value,
                        ]);
                })
                ->orWhere(function ($subQuery) {
                    $subQuery
                        ->where('method', PaymentMethod::NoMethod->value)
                        ->where('status', PaymentStatus::Draft->value);
                });
        })
        ->orderBy('id')
        ->chunkById(100, function ($payments) use (&$updated) {
            foreach ($payments as $payment) {
                $payment->status = PaymentStatus::Overdue;
                $payment->paid_at = null;
                $payment->save();

                $updated++;
            }
        });

    $this->info("Позначено як overdue: {$updated}");
})->purpose('Mark overdue policy payments and recompute related policy statuses');

Schedule::command('payments:mark-overdue')->everyFifteenMinutes();
Schedule::command('policies:notify-expiring')->dailyAt('09:00');