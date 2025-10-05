<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'individual',
            'status' => 'active',
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'primary_email' => $this->faker->safeEmail(),
            'primary_phone' => $this->faker->phoneNumber(),
            'city' => $this->faker->city(),
            'address_line' => $this->faker->streetAddress(),
            'source' => $this->faker->randomElement(['online', 'office', 'referral']),
        ];
    }
}
