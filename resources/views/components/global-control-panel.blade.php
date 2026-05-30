@props([
    'title' => 'Dashboard',
    'period' => 'tháng',
    'lastUpdate' => '08:30 - Cập nhật thành công',
])

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/client/css/components/global-control-panel.css') }}?v={{ time() }}">
    @endpush
@endonce

@php
    $periods = ['ngày' => 'Ngày', 'tháng' => 'Tháng', 'năm' => 'Năm'];
@endphp

<div {{ $attributes->merge(['class' => 'global-control-panel']) }}>
    <div>
        <h1 class="global-control-panel__title">{{ $title }}</h1>

        <p class="global-control-panel__last-update">
            <span class="global-control-panel__clock">⏱</span>
            <span>{{ $lastUpdate }}</span>
        </p>
    </div>

    <div class="global-control-panel__controls">
        <div class="global-control-panel__period-group">
            <span class="global-control-panel__period-label">Kỳ báo cáo:</span>

            <div class="global-control-panel__period-selector">
                @foreach ($periods as $key => $label)
                    <button
                        type="button"
                        class="global-control-panel__period-btn {{ $period === $key ? 'global-control-panel__period-btn--active' : '' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <button type="button" class="global-control-panel__export-btn">
            <span class="global-control-panel__export-icon">⇩</span>
            <span>Xuất báo cáo</span>
        </button>

        <button type="button" class="global-control-panel__refresh-btn">
            <span class="global-control-panel__refresh-icon">↻</span>
            <span>Làm mới</span>
        </button>
    </div>
</div>