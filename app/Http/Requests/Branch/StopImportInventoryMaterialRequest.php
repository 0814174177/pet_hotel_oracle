<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StopImportInventoryMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Move branch inventory stop-import permission checks into a policy or middleware when available.
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
