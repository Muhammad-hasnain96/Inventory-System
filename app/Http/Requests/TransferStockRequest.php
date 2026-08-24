<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isBranchManager();
    }

    public function rules(): array
    {
        return [
            'to_branch_id' => 'required|integer|exists:branches,id|not_in:' . auth()->user()->branch_id,
            'quantity' => 'required|integer|min:1|max:100000',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'to_branch_id.not_in' => 'Cannot transfer stock to the same branch',
            'to_branch_id.exists' => 'Destination branch does not exist',
        ];
    }
}
