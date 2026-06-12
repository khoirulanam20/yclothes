<?php

namespace App\Http\Requests\Api\Pos;

use Illuminate\Foundation\Http\FormRequest;

class CreatePosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,transfer'],
            'payments.*.amount' => ['required', 'integer', 'min:1'],
            'payments.*.payment_bank_id' => ['nullable', 'integer', 'exists:payment_banks,id'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
