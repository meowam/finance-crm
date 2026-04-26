<?php

namespace Database\Seeders;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PasswordResetRequestSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');

        DB::table('password_reset_requests')->delete();

        $admin = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->first();

        $requestUsers = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['manager', 'supervisor'])
            ->orderBy('id')
            ->get();

        if ($requestUsers->isEmpty()) {
            return;
        }

        $pendingUsers = $requestUsers->take(3);

        foreach ($pendingUsers as $user) {
            $createdAt = Carbon::instance($faker->dateTimeBetween(now()->subDays(5), now()->subHours(2)));

            PasswordResetRequest::query()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'resolved_by_id' => null,
                'resolved_at' => null,
                'comment' => 'Користувач просить змінити пароль вручну.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $resolvedUsers = $requestUsers
            ->skip(3)
            ->take(5);

        foreach ($resolvedUsers as $user) {
            $createdAt = Carbon::instance($faker->dateTimeBetween(now()->subWeeks(4), now()->subWeeks(1)));
            $resolvedAt = $createdAt->copy()->addDays(rand(1, 3));

            PasswordResetRequest::query()->create([
                'user_id' => $user->id,
                'status' => $faker->randomElement(['resolved', 'resolved', 'rejected']),
                'resolved_by_id' => $admin?->id,
                'resolved_at' => $resolvedAt,
                'comment' => $faker->randomElement([
                    'Пароль змінено адміністратором після перевірки користувача.',
                    'Запит оброблено вручну.',
                    'Запит відхилено після уточнення з користувачем.',
                ]),
                'created_at' => $createdAt,
                'updated_at' => $resolvedAt,
            ]);
        }

        $this->command?->info('Password reset requests:');

        DB::table('password_reset_requests')
            ->select('status', DB::raw('count(*) c'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->each(fn ($row) => $this->command?->info(" - {$row->status}: {$row->c}"));
    }
}