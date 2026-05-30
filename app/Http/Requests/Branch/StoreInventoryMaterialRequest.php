<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Move branch inventory create permission checks into a policy or middleware when available.
        return true;
    }

    public function rules(): array
    {
        return [
            'material_code' => ['nullable', 'string', 'max:255'],
            'material_name' => ['required', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
            'warning_threshold' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable'],
            'status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
