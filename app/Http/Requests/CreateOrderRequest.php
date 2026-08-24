<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isSuperAdmin() 
            || auth()->user()?->isBranchManager()
            || auth()->user()?->isSalesUser();
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|integer|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch selection is required',
            'branch_id.exists' => 'Selected branch does not exist',
            'items.required' => 'Order must contain at least one item',
            'items.min' => 'Order must contain at least one item',
            'items.*.product_id.exists' => 'One or more products do not exist',
            'items.*.quantity.min' => 'Each item quantity must be at least 1',
        ];
    }
}
