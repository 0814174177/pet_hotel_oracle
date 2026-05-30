<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Move branch inventory update permission checks into a policy or middleware when available.
        return true;
    }

    public function rules(): array
    {
        return [
            'material_code' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'warning_threshold' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable'],
            'status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
