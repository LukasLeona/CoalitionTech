<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_name',
        'quantity_in_stock',
        'price_per_item',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_in_stock' => 'integer',
            'price_per_item' => 'decimal:2',
        ];
    }

    public function pricePerItemFormatted(): string
    {
        return self::formatCents($this->pricePerItemInCents());
    }

    public function totalValueFormatted(): string
    {
        return self::formatCents($this->totalValueInCents());
    }

    public function totalValueInCents(): int
    {
        return $this->pricePerItemInCents() * $this->quantity_in_stock;
    }

    public static function formatCents(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absoluteAmount = abs($amount);

        return sprintf(
            '%s%d.%02d',
            $sign,
            intdiv($absoluteAmount, 100),
            $absoluteAmount % 100,
        );
    }

    private function pricePerItemInCents(): int
    {
        $amount = (string) $this->price_per_item;

        if (! preg_match('/^(?<sign>-?)(?<units>\d+)(?:\.(?<fraction>\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('The product price must be a decimal with at most two places.');
        }

        $fraction = str_pad($matches['fraction'] ?? '', 2, '0');
        $cents = ((int) $matches['units'] * 100) + (int) $fraction;

        return ($matches['sign'] ?? '') === '-' ? -$cents : $cents;
    }
}
