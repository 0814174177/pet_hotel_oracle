<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class InventoryMaterialIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Move branch inventory view permission checks into a policy or middleware when available.
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
