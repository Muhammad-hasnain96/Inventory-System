<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products',
            'sku' => 'required|string|max:100|unique:products',
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'required|numeric|min:0.01',
            'sale_price' => 'required|numeric|min:0.01',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU must be unique. This SKU is already used.',
            'sale_price.min' => 'Sale price must be at least 0.01',
        ];
    }
}
