<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client' => ['required', 'array'],
            'client.name' => ['required', 'string', 'max:255'],
            'client.email' => ['required', 'email', 'max:255'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'card_number' => ['required', 'digits:16'],
            'cvv' => ['required', 'digits:3'],
        ];
    }
}
