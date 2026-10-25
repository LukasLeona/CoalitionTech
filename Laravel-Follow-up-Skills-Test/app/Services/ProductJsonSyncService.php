<?php

namespace App\Services;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductJsonSyncService
{
    /**
     * @return array{data: list<array<string, mixed>>, meta: array{grand_total: string, record_count: int, total_units: int}}
     */
    public function snapshot(): array
    {
        $products = $this->orderedProducts();
        $grandTotalInCents = 0;
        $totalUnits = 0;

        $data = $products
            ->map(function (Product $product) use (&$grandTotalInCents, &$totalUnits): array {
                $grandTotalInCents += $product->totalValueInCents();
                $totalUnits += $product->quantity_in_stock;

                return (new ProductResource($product))->resolve();
            })
            ->values()
            ->all();

        return [
            'data' => $data,
            'meta' => [
                'grand_total' => Product::formatCents($grandTotalInCents),
                'record_count' => $products->count(),
                'total_units' => $totalUnits,
            ],
        ];
    }

    /**
     * Persist and return the current product snapshot.
     *
     * @return array{data: list<array<string, mixed>>, meta: array{grand_total: string, record_count: int, total_units: int}}
     */
    public function sync(): array
    {
        $snapshot = $this->snapshot();
        $json = json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        if (! Storage::disk('local')->put('products.json', $json.PHP_EOL)) {
            throw new RuntimeException('Unable to synchronize the product JSON file.');
        }

        return $snapshot;
    }

    /**
     * @return Collection<int, Product>
     */
    private function orderedProducts(): Collection
    {
        return Product::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
