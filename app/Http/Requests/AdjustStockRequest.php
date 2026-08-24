<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isBranchManager();
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:-100000|max:100000|not_in:0',
            'notes' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.not_in' => 'Quantity cannot be zero',
            'notes.required' => 'Reason for adjustment is required',
        ];
    }
}
