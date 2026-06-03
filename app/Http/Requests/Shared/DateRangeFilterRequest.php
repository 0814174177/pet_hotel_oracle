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
        if ($this->filled('start_date')) {
            return Carbon::parse($this->validated('start_date'))->startOfDay();
        }

        return $this->shouldUseDefaultDateRange()
            ? $this->defaultStartDate()
            : null;
    }

    public function getEndDate(): ?Carbon
    {
        if ($this->filled('end_date')) {
            return Carbon::parse($this->validated('end_date'))->endOfDay();
        }

        return $this->shouldUseDefaultDateRange()
            ? $this->defaultEndDate()
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

    private function shouldUseDefaultDateRange(): bool
    {
        return ! $this->filled('start_date') && ! $this->filled('end_date');
    }

    private function defaultStartDate(): Carbon
    {
        return Carbon::today(config('app.timezone'))->subDays(2)->startOfDay();
    }

    private function defaultEndDate(): Carbon
    {
        return Carbon::today(config('app.timezone'))->subDay()->endOfDay();
    }
}
