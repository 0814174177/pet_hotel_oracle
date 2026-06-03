<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

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

    protected function respondData(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $extra = [],
        ?string $errorMessage = null
    ): JsonResponse {
        try {
            $resolvedData = $data instanceof Closure ? $data() : $data;
        } catch (Throwable $exception) {
            if ($errorMessage === null) {
                throw $exception;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }

        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data' => $resolvedData,
        ], $extra), $status);
    }
}
