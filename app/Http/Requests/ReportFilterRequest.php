<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter_type' => ['nullable', 'string', 'in:day,month,year,custom,range'],
            'period_type' => ['nullable', 'string', 'in:day,month,year,custom,range'],
            'period' => ['nullable', 'string', 'in:day,month,year,custom,range'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:1', 'max:'.$this->today()->year],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'high_cancel_amount' => ['nullable', 'numeric', 'min:0'],
            'cancel_rate_threshold' => ['nullable', 'numeric', 'between:0,100'],
            'action_count_threshold' => ['nullable', 'integer', 'min:1'],
            'revenue_drop_threshold' => ['nullable', 'numeric', 'max:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filterType = $this->rawFilterType();

            if ($filterType === 'custom' && (! $this->filled('start_date') || ! $this->filled('end_date'))) {
                $validator->errors()->add('start_date', 'Khoảng thời gian tùy chỉnh cần có ngày bắt đầu và ngày kết thúc.');
            }

            if ($filterType !== 'month') {
                return;
            }

            $year = (int) ($this->input('year') ?: $this->today()->year);
            $month = (int) ($this->input('month') ?: $this->today()->month);

            if ($year < 1 || $year > $this->today()->year || $month < 1 || $month > 12) {
                return;
            }

            $selectedMonth = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));

            if ($selectedMonth->startOfMonth()->greaterThan($this->today()->startOfMonth())) {
                $validator->errors()->add('month', 'Tháng báo cáo không được nằm trong tương lai.');
            }
        });
    }

    public function reportFilters(): array
    {
        $validated = $this->validated();
        $filterType = $this->normalizeFilterType(
            $validated['filter_type']
                ?? $validated['period_type']
                ?? $validated['period']
                ?? $this->defaultFilterType()
        );

        [$startDate, $endDate] = $this->currentRange($filterType, $validated);
        [$prevStartDate, $prevEndDate] = $this->previousRange($filterType, $startDate, $endDate);

        $filters = [
            'filter_type' => $filterType,
            'period_type' => $filterType === 'custom' ? 'range' : $filterType,
            'period' => $filterType === 'custom' ? 'range' : $filterType,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'prev_start_date' => $prevStartDate->toDateString(),
            'prev_end_date' => $prevEndDate->toDateString(),
        ];

        foreach ([
            'high_cancel_amount',
            'cancel_rate_threshold',
            'action_count_threshold',
            'revenue_drop_threshold',
        ] as $thresholdKey) {
            if (isset($validated[$thresholdKey])) {
                $filters[$thresholdKey] = $thresholdKey === 'action_count_threshold'
                    ? (int) $validated[$thresholdKey]
                    : (float) $validated[$thresholdKey];
            }
        }

        return $filters;
    }

    private function currentRange(string $filterType, array $filters): array
    {
        $today = $this->today();

        if ($filterType === 'day') {
            $date = ! empty($filters['date'])
                ? CarbonImmutable::parse($filters['date'], config('app.timezone'))
                : $today;

            return [$date, $date];
        }

        if ($filterType === 'year') {
            $year = (int) ($filters['year'] ?? $today->year);
            $startDate = CarbonImmutable::create($year, 1, 1, 0, 0, 0, config('app.timezone'));

            return [$startDate, $startDate->endOfYear()];
        }

        if ($filterType === 'custom') {
            return [
                CarbonImmutable::parse($filters['start_date'], config('app.timezone')),
                CarbonImmutable::parse($filters['end_date'], config('app.timezone')),
            ];
        }

        $year = (int) ($filters['year'] ?? $today->year);
        $month = (int) ($filters['month'] ?? $today->month);
        $startDate = CarbonImmutable::create($year, $month, 1, 0, 0, 0, config('app.timezone'));

        return [$startDate, $startDate->endOfMonth()];
    }

    private function previousRange(string $filterType, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        if ($filterType === 'day') {
            $previousDate = $startDate->subDay();

            return [$previousDate, $previousDate];
        }

        if ($filterType === 'month') {
            $previousStart = $startDate->subMonthNoOverflow()->startOfMonth();

            return [$previousStart, $previousStart->endOfMonth()];
        }

        if ($filterType === 'year') {
            $previousStart = $startDate->subYear()->startOfYear();

            return [$previousStart, $previousStart->endOfYear()];
        }

        $daysInPeriod = abs((int) $startDate->diffInDays($endDate)) + 1;
        $previousEnd = $startDate->subDay();
        $previousStart = $previousEnd->subDays($daysInPeriod - 1);

        return [$previousStart, $previousEnd];
    }

    private function rawFilterType(): string
    {
        return $this->normalizeFilterType(
            $this->input('filter_type')
                ?? $this->input('period_type')
                ?? $this->input('period')
                ?? $this->defaultFilterType()
        );
    }

    private function defaultFilterType(): string
    {
        return $this->filled('start_date') || $this->filled('end_date')
            ? 'custom'
            : 'month';
    }

    private function normalizeFilterType(string $filterType): string
    {
        return $filterType === 'range' ? 'custom' : $filterType;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::today(config('app.timezone'));
    }
}
