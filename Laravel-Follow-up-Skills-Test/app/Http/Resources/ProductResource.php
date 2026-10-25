<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'quantity_in_stock' => (int) $this->quantity_in_stock,
            'price_per_item' => $this->pricePerItemFormatted(),
            'total_value' => $this->totalValueFormatted(),
            'submitted_at_iso' => $this->created_at?->toIso8601String(),
            'submitted_at_display' => $this->created_at?->format('M j, Y, g:i A'),
        ];
    }
}
