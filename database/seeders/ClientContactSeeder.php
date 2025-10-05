<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Client;
use App\Models\ClientContact;

class ClientContactSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('uk_UA');

        foreach (Client::all() as $client) {
            $count = rand(1, 3);

            for ($i = 0; $i < $count; $i++) {
                $type = $faker->randomElement(['email', 'phone', 'telegram']);
                $value = null;

                // --- логіка генерації під тип ---
                switch ($type) {
                    case 'email':
                        $value = $faker->unique()->safeEmail();
                        break;

                    case 'phone':
                        // український формат
                        $value = '+380' . $faker->numberBetween(50, 99) . $faker->numerify('#######');
                        break;

                    case 'telegram':
                        // 50% — нік, 50% — номер
                        if ($faker->boolean(50)) {
                            $value = '@' . $faker->userName();
                        } else {
                            $value = '+380' . $faker->numberBetween(50, 99) . $faker->numerify('#######');
                        }
                        break;
                }

                ClientContact::create([
                    'client_id' => $client->id,
                    'type' => $type,
                    'value' => $value,
                    'label' => $faker->randomElement(['робочий', 'особистий', 'додатковий']),
                    'notes' => null,
                ]);
            }
        }
    }
}
