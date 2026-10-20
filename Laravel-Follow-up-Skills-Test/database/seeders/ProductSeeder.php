<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductJsonSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProductJsonSyncService $productJsonSyncService): void
    {
        Product::query()->delete();

        Product::factory()->createMany([
            [
                'product_name' => 'Wireless Mechanical Keyboard',
                'quantity_in_stock' => 15,
                'price_per_item' => '79.99',
                'created_at' => CarbonImmutable::parse('2026-10-20 08:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-10-20 08:00:00'),
            ],
            [
                'product_name' => 'Ergonomic Wireless Mouse',
                'quantity_in_stock' => 24,
                'price_per_item' => '39.50',
                'created_at' => CarbonImmutable::parse('2026-10-20 08:15:00'),
                'updated_at' => CarbonImmutable::parse('2026-10-20 08:15:00'),
            ],
            [
                'product_name' => 'Professional USB-C Dock',
                'quantity_in_stock' => 8,
                'price_per_item' => '149.00',
                'created_at' => CarbonImmutable::parse('2026-10-20 08:30:00'),
                'updated_at' => CarbonImmutable::parse('2026-10-20 08:30:00'),
            ],
        ]);

        $productJsonSyncService->sync();
    }
}
