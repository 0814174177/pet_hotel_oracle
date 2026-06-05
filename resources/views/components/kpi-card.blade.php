@props([
    'title' => '',
    'value' => '',
    'trend' => null,
    'detail' => null,
    'isPositive' => true,
    'period' => 'tháng',
    'icon' => null,
    'valueAttributes' => [],
])

@include('components.kpi_card', [
    'title' => $title,
    'value' => $value,
    'trend' => $trend,
    'detail' => $detail,
    'isPositive' => $isPositive,
    'period' => $period,
    'icon' => $icon,
    'valueAttributes' => $valueAttributes,
])
