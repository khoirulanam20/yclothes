<?php

namespace App\Http\Requests\Api\Pos;

use Illuminate\Foundation\Http\FormRequest;

class OpenShiftRequest extends FormRequest
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
            'opening_cash' => ['nullable', 'integer', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
