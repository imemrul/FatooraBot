<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'barcode' => fake()->ean13(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'cost_price' => fake()->randomFloat(2, 5, 3000),
            'vat_rate' => 5.00,
            'unit' => fake()->randomElement(['unit', 'hour', 'kg', 'piece']),
            'low_stock_threshold' => 10,
            'is_active' => true,
        ];
    }
}
