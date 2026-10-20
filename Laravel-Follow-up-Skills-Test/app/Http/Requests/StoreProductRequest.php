<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_name' => ['bail', 'required', 'string', 'max:255'],
            'quantity_in_stock' => ['bail', 'required', 'integer', 'min:0', 'max:1000000'],
            'price_per_item' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_name' => 'product name',
            'quantity_in_stock' => 'quantity in stock',
            'price_per_item' => 'price per item',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('product_name')) {
            $this->merge([
                'product_name' => trim((string) $this->input('product_name')),
            ]);
        }
    }
}
