<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Main Warehouse', 'Branch Store', 'Cold Storage', 'Showroom']) . ' ' . fake()->numberBetween(1, 9),
            'location' => fake()->address(),
            'is_active' => true,
        ];
    }
}
