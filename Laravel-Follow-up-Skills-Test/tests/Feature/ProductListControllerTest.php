<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductListControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_products_newest_first_with_totals_and_a_stable_tie_breaker(): void
    {
        Product::factory()->create([
            'product_name' => 'Older product',
            'quantity_in_stock' => 2,
            'price_per_item' => '12.50',
            'created_at' => '2026-10-20 09:00:00',
        ]);
        Product::factory()->create([
            'product_name' => 'First tied product',
            'quantity_in_stock' => 3,
            'price_per_item' => '7.25',
            'created_at' => '2026-10-20 10:00:00',
        ]);
        Product::factory()->create([
            'product_name' => 'Last tied product',
            'quantity_in_stock' => 1,
            'price_per_item' => '5.00',
            'created_at' => '2026-10-20 10:00:00',
        ]);

        $response = $this->getJson(route('products.list'));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.product_name', 'Last tied product')
            ->assertJsonPath('data.1.product_name', 'First tied product')
            ->assertJsonPath('data.2.product_name', 'Older product')
            ->assertJsonPath('data.0.total_value', '5.00')
            ->assertJsonPath('meta.grand_total', '51.75')
            ->assertJsonPath('meta.record_count', 3)
            ->assertJsonPath('meta.total_units', 6);
    }
}
