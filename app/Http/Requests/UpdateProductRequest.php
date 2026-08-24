<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        $productId = $this->route('product')->id ?? $this->route('product');
        
        return [
            'name' => 'required|string|max:255|unique:products,name,' . $productId,
            'sku' => 'required|string|max:100|unique:products,sku,' . $productId,
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'required|numeric|min:0.01',
            'sale_price' => 'required|numeric|min:0.01',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ];
    }
}
