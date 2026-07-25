<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'trade_license_number' => fake()->numerify('TL-######'),
            'tax_registration_number' => fake()->numerify('###############'),
            'address' => fake()->address(),
            'city' => fake()->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman']),
            'country' => 'AE',
            'currency' => 'AED',
            'is_active' => true,
            'onboarded_at' => now(),
        ];
    }

    public function notOnboarded(): static
    {
        return $this->state(fn () => [
            'trade_license_number' => null,
            'tax_registration_number' => null,
            'address' => null,
            'city' => null,
            'onboarded_at' => null,
        ]);
    }
}
