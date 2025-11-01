<?php
namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $faker         = Faker::create('uk_UA');
        $managers      = User::where('role', 'manager')->pluck('id')->toArray();
        $operatorCodes = [39, 50, 63, 66, 67, 68, 73, 91, 92, 93, 94, 95, 96, 97, 98, 99];

        for ($i = 0; $i < 20; $i++) {
            $isMale    = $faker->boolean(50);
            $firstName = $isMale ? $faker->firstNameMale() : $faker->firstNameFemale();
            $lastName  = $faker->lastName();

            $fatherName    = $faker->firstNameMale();
            $hasPatronymic = $faker->boolean(80);
            $middleName    = $hasPatronymic ? $this->uaPatronymic($fatherName, $isMale) : null;

            $document = strtoupper($faker->regexify('[A-Z]{2}[0-9]{6}'));

            $type         = $faker->randomElement(['individual', 'company']);
            $isIndividual = $type === 'individual';
            $phone        = '+380' . $faker->randomElement($operatorCodes) . $faker->numerify('#######');
            $createdAt    = Carbon::create(2025, 10, rand(1, 31), rand(0, 23), rand(0, 59), rand(0, 59));
            Client::create([
                'type'                     => $type,
                'status'                   => $faker->randomElement(['lead', 'active', 'archived']),
                'first_name'               => $firstName,
                'last_name'                => $lastName,
                'middle_name'              => $middleName,
                'company_name'             => $isIndividual ? null : $faker->company(),
                'primary_email'            => $faker->unique()->safeEmail(),
                'primary_phone'            => $phone,
                'document_number'          => $document,
                'tax_id'                   => $faker->numerify('##########'),
                'date_of_birth'            => $faker->dateTimeBetween('-73 years', '-18 years'),
                'preferred_contact_method' => $faker->randomElement(['phone', 'email']),
                'city'                     => $faker->city(),
                'address_line'             => $faker->streetAddress(),
                'source'                   => $faker->randomElement(['office', 'online', 'recommendation']), //, 'рекомендація'

                'assigned_user_id'         => count($managers) ? $faker->randomElement($managers) : null,
                'created_at'               => $createdAt,
                'notes'                    => null,
            ]);
        }
    }

    protected function uaPatronymic(string $fatherName, bool $male): string
    {
        $name       = trim($fatherName);
        $exceptions = ['Ілля' => $male ? 'Ілліч' : 'Іллівна'];
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
