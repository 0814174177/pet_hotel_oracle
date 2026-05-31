<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class DateRangeFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function getStartDate(): ?Carbon
    {
        return $this->filled('start_date')
            ? Carbon::parse($this->validated('start_date'))->startOfDay()
            : null;
    }

    public function getEndDate(): ?Carbon
    {
        return $this->filled('end_date')
            ? Carbon::parse($this->validated('end_date'))->endOfDay()
            : null;
    }

    public function getFiltersArray(): array
    {
        return [
            'start_date' => $this->getStartDate(),
            'end_date' => $this->getEndDate(),
        ];
    }
}
