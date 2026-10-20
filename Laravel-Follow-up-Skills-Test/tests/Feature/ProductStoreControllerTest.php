<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductStoreControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_payload_creates_product_returns_201_and_synchronizes_json(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-10-20 17:20:00');

        $response = $this->postJson(route('products.store'), [
            'product_name' => '  Studio Monitor Stand  ',
            'quantity_in_stock' => 3,
            'price_per_item' => '19.90',
            'created_at' => '1999-01-01 00:00:00',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Product added successfully.')
            ->assertJsonPath('data.0.product_name', 'Studio Monitor Stand')
            ->assertJsonPath('data.0.quantity_in_stock', 3)
            ->assertJsonPath('data.0.price_per_item', '19.90')
            ->assertJsonPath('data.0.total_value', '59.70')
            ->assertJsonPath('meta.grand_total', '59.70');

        $this->assertDatabaseHas('products', [
            'product_name' => 'Studio Monitor Stand',
            'quantity_in_stock' => 3,
            'price_per_item' => 19.90,
            'created_at' => '2026-10-20 17:20:00',
        ]);

        $product = Product::query()->sole();
        $this->assertArrayNotHasKey('unexpected', $product->getAttributes());

        Storage::disk('local')->assertExists('products.json');
        $snapshot = json_decode(Storage::disk('local')->get('products.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Studio Monitor Stand', $snapshot['data'][0]['product_name']);
        $this->assertSame('59.70', $snapshot['meta']['grand_total']);
    }

    public function test_returns_422_with_messages_when_required_fields_are_missing(): void
    {
        Storage::fake('local');

        $response = $this->postJson(route('products.store'));

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.product_name.0', 'The product name field is required.')
            ->assertJsonPath('errors.quantity_in_stock.0', 'The quantity in stock field is required.')
            ->assertJsonPath('errors.price_per_item.0', 'The price per item field is required.');

        $this->assertDatabaseCount('products', 0);
        Storage::disk('local')->assertMissing('products.json');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidProductProvider')]
    public function test_returns_422_with_message_for_invalid_product_values(
        array $payload,
        string $field,
        string $message,
    ): void {
        $response = $this->postJson(route('products.store'), $payload);

        $response
            ->assertUnprocessable()
            ->assertJsonPath("errors.{$field}.0", $message);

        $this->assertDatabaseCount('products', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidProductProvider(): array
    {
        return [
            'blank product name' => [[
                'product_name' => '   ',
                'quantity_in_stock' => 2,
                'price_per_item' => '10.00',
            ], 'product_name', 'The product name field is required.'],
            'negative quantity' => [[
                'product_name' => 'Desk lamp',
                'quantity_in_stock' => -1,
                'price_per_item' => '10.00',
            ], 'quantity_in_stock', 'The quantity in stock field must be at least 0.'],
            'price with excess precision' => [[
                'product_name' => 'Desk lamp',
                'quantity_in_stock' => 2,
                'price_per_item' => '10.999',
            ], 'price_per_item', 'The price per item field must have 0-2 decimal places.'],
        ];
    }
}
