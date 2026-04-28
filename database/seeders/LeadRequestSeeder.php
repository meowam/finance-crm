<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\LeadRequest;
use App\Models\User;
use App\Services\Assignments\ManagerAssignmentService;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadRequestSeeder extends Seeder
{
    protected array $operatorCodes = [
        39, 50, 63, 66, 67, 68, 73, 91, 92, 93, 94, 95, 96, 97, 98, 99,
    ];

    public function run(): void
    {
        $faker = Faker::create('uk_UA');

        $managers = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->get();

        if ($managers->isEmpty()) {
            return;
        }

        DB::table('lead_requests')->delete();

        $convertedClients = Client::query()
            ->whereNotNull('assigned_user_id')
            ->whereIn('status', ['active', 'lead'])
            ->limit(8)
            ->get();

        foreach ($convertedClients as $client) {
            LeadRequest::query()->create([
                'type' => $client->type,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'middle_name' => $client->middle_name,
                'company_name' => $client->type === 'company' ? $client->company_name : null,
                'phone' => $client->primary_phone,
                'email' => $client->primary_email,
                'interest' => $this->randomInterest($faker),
                'source' => $faker->randomElement(['landing', 'manual', 'recommendation']),
                'status' => 'converted',
                'comment' => 'Лід уже конвертовано у клієнта.',
                'assigned_user_id' => $client->assigned_user_id,
                'converted_client_id' => $client->id,
                'created_at' => $this->randomCarbonBetween(
                    Carbon::now()->subMonths(3),
                    Carbon::now()->subWeeks(2),
                ),
                'updated_at' => Carbon::now()->subDays(random_int(1, 10)),
            ]);
        }

        $statusPool = [
            'new',
            'new',
            'new',
            'new',
            'in_progress',
            'in_progress',
            'in_progress',
            'rejected',
        ];

        $sources = [
            'landing',
            'landing',
            'landing',
            'manual',
            'manual',
            'manual',
            'recommendation',
            'recommendation',
        ];

        for ($i = 0; $i < 32; $i++) {
            $type = $faker->randomElement(['individual', 'individual', 'individual', 'company']);
            $isCompany = $type === 'company';
            $isMale = $faker->boolean();

            $firstName = $isMale ? $faker->firstNameMale() : $faker->firstNameFemale();
            $lastName = $faker->lastName();
            $middleName = $faker->boolean(75)
                ? $this->uaPatronymic($faker->firstNameMale(), $isMale)
                : null;

            $managerId = app(ManagerAssignmentService::class)->resolveLeastBusyManagerId()
                ?: $managers->random()->id;

            $status = $faker->randomElement($statusPool);
            $createdAt = $this->randomCarbonBetween(
                Carbon::now()->subMonths(2),
                Carbon::now(),
            );

            LeadRequest::query()->create([
                'type' => $type,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'company_name' => $isCompany ? $faker->company() : null,
                'phone' => '+380' . $faker->randomElement($this->operatorCodes) . $faker->numerify('#######'),
                'email' => $faker->boolean(85) ? $faker->unique()->safeEmail() : null,
                'interest' => $this->randomInterest($faker),
                'source' => $faker->randomElement($sources),
                'status' => $status,
                'comment' => $this->commentForStatus($status, $faker),
                'assigned_user_id' => $managerId,
                'converted_client_id' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays(random_int(0, 10))->min(Carbon::now()),
            ]);
        }

        $this->command?->info('Lead requests:');

        DB::table('lead_requests')
            ->select([
                'status',
                DB::raw('count(*) as c'),
            ])
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->each(fn ($row) => $this->command?->info(" - {$row->status}: {$row->c}"));
    }

    protected function randomCarbonBetween(Carbon $start, Carbon $end): Carbon
    {
        if ($end->lessThan($start)) {
            return $start->copy();
        }

        return Carbon::createFromTimestamp(
            random_int($start->timestamp, $end->timestamp)
        );
    }

    protected function randomInterest(FakerGenerator $faker): string
    {
        return $faker->randomElement([
            'Автострахування',
            'Страхування майна',
            'Здоров’я та життя',
            'Страхування подорожей',
            'Корпоративні програми',
            'Індивідуальне рішення',
            'Інше',
        ]);
    }

    protected function commentForStatus(string $status, FakerGenerator $faker): ?string
    {
        return match ($status) {
            'new' => $faker->randomElement([
                'Клієнт залишив заявку на сайті, очікує дзвінка.',
                'Потрібно уточнити деталі запиту.',
                'Заявка з лендінгу, клієнт просить швидку консультацію.',
            ]),
            'in_progress' => $faker->randomElement([
                'Менеджер уже зв’язався з клієнтом, очікується уточнення документів.',
                'Клієнт порівнює кілька варіантів страхування.',
                'Потрібно підготувати пропозицію за кількома продуктами.',
            ]),
            'rejected' => $faker->randomElement([
                'Клієнт відмовився через вартість.',
                'Клієнт уже оформив поліс в іншій компанії.',
                'Некоректні контактні дані, зв’язатися не вдалося.',
            ]),
            default => null,
        };
    }

    protected function uaPatronymic(string $fatherName, bool $male): string
    {
        $name = trim($fatherName);

        $exceptions = [
            'Ілля' => $male ? 'Ілліч' : 'Іллівна',
        ];

        if (array_key_exists($name, $exceptions)) {
            return $exceptions[$name];
        }

        $last = mb_substr($name, -1, null, 'UTF-8');

        if ($last === 'й') {
            return $male
                ? $name . 'ович'
                : mb_substr($name, 0, -1, 'UTF-8') . 'ївна';
        }

        if ($last === 'а') {
            return $male ? $name . 'йович' : $name . 'ївна';
        }

        if ($last === 'о') {
            $base = mb_substr($name, 0, -1, 'UTF-8');

            return $male ? $base . 'ович' : $base . 'івна';
        }

        return $male ? $name . 'ович' : $name . 'івна';
    }
}