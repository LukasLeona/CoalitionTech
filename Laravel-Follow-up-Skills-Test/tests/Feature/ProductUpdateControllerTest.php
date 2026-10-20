<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductUpdateControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_payload_updates_product_and_synchronizes_json(): void
    {
        Storage::fake('local');
        $product = Product::factory()->create([
            'product_name' => 'USB microphone',
            'quantity_in_stock' => 5,
            'price_per_item' => '80.00',
            'created_at' => '2026-10-20 10:00:00',
        ]);

        $response = $this->patchJson(route('products.update', $product), [
            'product_name' => 'Broadcast USB microphone',
            'quantity_in_stock' => 7,
            'price_per_item' => '84.50',
            'created_at' => '1999-01-01 00:00:00',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Product updated successfully.')
            ->assertJsonPath('data.0.product_name', 'Broadcast USB microphone')
            ->assertJsonPath('data.0.total_value', '591.50')
            ->assertJsonPath('meta.grand_total', '591.50');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => 'Broadcast USB microphone',
            'quantity_in_stock' => 7,
            'price_per_item' => 84.50,
            'created_at' => '2026-10-20 10:00:00',
        ]);

        $snapshot = json_decode(Storage::disk('local')->get('products.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Broadcast USB microphone', $snapshot['data'][0]['product_name']);
        $this->assertSame('591.50', $snapshot['meta']['grand_total']);
    }

    public function test_returns_404_when_product_does_not_exist(): void
    {
        $response = $this->patchJson(route('products.update', 999999), [
            'product_name' => 'Missing product',
            'quantity_in_stock' => 1,
            'price_per_item' => '5.00',
        ]);

        $response->assertNotFound();
    }
}
