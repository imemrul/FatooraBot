<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_registration_number' => fake()->numerify('###############'),
            'credit_limit' => fake()->randomElement([0, 5000, 10000, 50000, 100000]),
            'payment_terms' => fake()->randomElement([15, 30, 45, 60]),
            'address' => fake()->address(),
            'city' => fake()->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman']),
            'country' => 'AE',
        ];
    }
}
