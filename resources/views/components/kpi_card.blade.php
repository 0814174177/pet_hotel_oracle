@props([
    'title' => '',
    'value' => '',
    'trend' => null,
    'detail' => null,
    'isPositive' => true,
    'period' => 'tháng',
    'icon' => null,
])

@php
    $positiveValue = filter_var($isPositive, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    $positiveValue = $positiveValue ?? (bool) $isPositive;
    $hasTrend = filled($trend);
    $hasDetail = filled($detail);
@endphp

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/client/css/components/kpi-card.css') }}">
    @endpush
@endonce

<div {{ $attributes->merge(['class' => 'kpi-card-wrapper']) }}>
    <div class="kpi-card">
        <div class="kpi-card__content">
            <p class="kpi-card__title">{{ $title }}</p>
            <h3 class="kpi-card__value" data-kpi-value>{{ $value }}</h3>

            @if ($hasTrend)
                <p class="kpi-card__trend {{ $positiveValue ? 'kpi-card__trend--positive' : 'kpi-card__trend--negative' }}" data-kpi-trend>
                    <span class="kpi-card__arrow" data-kpi-arrow>{{ $positiveValue ? '▲' : '▼' }}</span>
                    <span class="kpi-card__trend-value" data-kpi-trend-value>{{ $trend }}</span>
                    <span class="kpi-card__period-label" data-kpi-period-label>so với {{ $period }} trước</span>
                </p>
            @endif

            @if ($hasDetail)
                <p class="kpi-card__detail" data-kpi-detail>{{ $detail }}</p>
            @endif
        </div>

        @if ($icon)
            <div class="kpi-card__icon-wrapper" aria-hidden="true">
                <i class="{{ $icon }}"></i>
            </div>
        @endif
    </div>
</div>
