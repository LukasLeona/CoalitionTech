<?php

namespace Tests\Feature;

use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_deterministic_inventory_and_synchronizes_json(): void
    {
        Storage::fake('local');

        $this->seed(ProductSeeder::class);

        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseHas('products', [
            'product_name' => 'Professional USB-C Dock',
            'quantity_in_stock' => 8,
            'price_per_item' => 149.00,
        ]);

        Storage::disk('local')->assertExists('products.json');
        $snapshot = json_decode(Storage::disk('local')->get('products.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Professional USB-C Dock', $snapshot['data'][0]['product_name']);
        $this->assertSame('3339.85', $snapshot['meta']['grand_total']);
        $this->assertSame(47, $snapshot['meta']['total_units']);

    }
}
