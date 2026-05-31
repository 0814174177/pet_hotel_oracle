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

    public function getPreviousStartDate(): ?Carbon
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if (! $startDate || ! $endDate) {
            return null;
        }

        $daysInPeriod = (int) $startDate->copy()
            ->startOfDay()
            ->diffInDays($endDate->copy()->startOfDay()) + 1;

        return $this->getPreviousEndDate()
            ?->copy()
            ->subDays($daysInPeriod - 1)
            ->startOfDay();
    }

    public function getPreviousEndDate(): ?Carbon
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if (! $startDate || ! $endDate) {
            return null;
        }

        return $startDate->copy()->subDay()->endOfDay();
    }

    public function getFiltersArray(): array
    {
        return [
            'start_date' => $this->getStartDate(),
            'end_date' => $this->getEndDate(),
            'prev_start_date' => $this->getPreviousStartDate(),
            'prev_end_date' => $this->getPreviousEndDate(),
        ];
    }
}
