<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_name' => fake()->unique()->words(3, true),
            'quantity_in_stock' => fake()->numberBetween(0, 500),
            'price_per_item' => fake()->randomFloat(2, 1, 2500),
        ];
    }
}
