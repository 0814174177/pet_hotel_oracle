<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    public static function timeFilterValidationRules(): array
    {
        return [
            'period_type' => ['nullable', 'string', 'in:day,month,year,range'],
            'period' => ['nullable', 'string', 'in:day,month,year,range'],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function timeFilters(Request $request): array
    {
        return $request->validate(self::timeFilterValidationRules());
    }

    protected function validateWithTimeFilters(Request $request, array $rules = []): array
    {
        return $request->validate(array_merge(self::timeFilterValidationRules(), $rules));
    }
}
