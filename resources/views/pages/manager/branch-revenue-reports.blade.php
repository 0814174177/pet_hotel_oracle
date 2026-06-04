@extends('layouts.manager')

@section('title', 'Báo cáo Doanh thu Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-revenue-reports.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $managerBranchId = auth()->user()?->employee?->branch_id;
    abort_if(blank($managerBranchId), 403, 'Manager account is not assigned to a branch.');

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: GROWTH TRACKING
    |--------------------------------------------------------------------------
    */
    $targetRevenue = 540;
    $currentRevenue = 381;
    $progress = min(($currentRevenue / $targetRevenue) * 100, 100);
    $remaining = $targetRevenue - $currentRevenue;
    $daysLeft = 8;
    $dailyNeeded = $remaining / $daysLeft;

    $revenueTrendData = [
        ['label' => 'T2', 'thisWeek' => 18.5, 'lastWeek' => 15.2],
        ['label' => 'T3', 'thisWeek' => 22.1, 'lastWeek' => 19.8],
        ['label' => 'T4', 'thisWeek' => 19.3, 'lastWeek' => 21.0],
        ['label' => 'T5', 'thisWeek' => 26.7, 'lastWeek' => 18.5],
        ['label' => 'T6', 'thisWeek' => 31.2, 'lastWeek' => 24.3],
        ['label' => 'T7', 'thisWeek' => 38.9, 'lastWeek' => 32.1],
        ['label' => 'CN', 'thisWeek' => 42.0, 'lastWeek' => 35.6],
    ];

    $revenueLineConfigs = [
        ['key' => 'thisWeek', 'name' => 'Kỳ này (tr)', 'color' => '#f59e0b'],
        ['key' => 'lastWeek', 'name' => 'Cùng kỳ trước (tr)', 'color' => '#475569'],
    ];

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: SERVICE MIX
    |--------------------------------------------------------------------------
    */
    $serviceData = [
        ['name' => 'Hotel Chó', 'value' => 38.2, 'color' => '#3b82f6', 'icon' => '🐕'],
        ['name' => 'Hotel Mèo', 'value' => 22.1, 'color' => '#8b5cf6', 'icon' => '🐱'],
        ['name' => 'Spa / Grooming', 'value' => 28.4, 'color' => '#f59e0b', 'icon' => '✂️'],
        ['name' => 'Thú y / Khám', 'value' => 7.8, 'color' => '#10b981', 'icon' => '💊'],
        ['name' => 'Phụ kiện / Shop', 'value' => 3.5, 'color' => '#64748b', 'icon' => '🛍️'],
    ];

    $serviceColors = array_column($serviceData, 'color');
    $hotelShare = $serviceData[0]['value'] + $serviceData[1]['value'];
    $spaShare = $serviceData[2]['value'];

    $aovData = [
        ['key' => 'current_aov', 'label' => 'Tháng này', 'value' => '485k', 'change' => '+12.4%', 'highlight' => true],
        ['key' => 'previous_aov', 'label' => 'Tháng trước', 'value' => '431.5k', 'change' => null, 'highlight' => false],
        ['key' => 'max_order_value', 'label' => 'Cao nhất/đơn', 'value' => '2.850k', 'change' => null, 'highlight' => false],
        ['key' => 'current_orders', 'label' => 'Số đơn/tháng', 'value' => '786 đơn', 'change' => null, 'highlight' => false],
    ];

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: STAFF PERFORMANCE
    |--------------------------------------------------------------------------
    */
    $staffData = [
        [
            'name' => 'Nguyễn Minh Tuấn',
            'role' => 'Thợ chính',
            'revenue' => 48.5,
            'orders' => 32,
            'upsellRate' => 68,
            'alert' => false,
        ],
        [
            'name' => 'Phạm Thu Hà',
            'role' => 'Thợ chính',
            'revenue' => 44.1,
            'orders' => 29,
            'upsellRate' => 55,
            'alert' => false,
        ],
        [
            'name' => 'Lê Văn Hùng',
            'role' => 'Thợ phụ',
            'revenue' => 39.7,
            'orders' => 41,
            'upsellRate' => 71,
            'alert' => true,
        ],
        [
            'name' => 'Trần Thị Lan',
            'role' => 'Lễ tân',
            'revenue' => 31.2,
            'orders' => 87,
            'upsellRate' => 82,
            'alert' => false,
        ],
        [
            'name' => 'Võ Thanh Bình',
            'role' => 'Thợ phụ',
            'revenue' => 22.8,
            'orders' => 19,
            'upsellRate' => 42,
            'alert' => false,
        ],
    ];

    $topRevenue = max(array_column($staffData, 'revenue'));

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: CUSTOMER ANALYTICS
    |--------------------------------------------------------------------------
    */
    $retentionData = [
        ['month' => 'T12/24', 'rate' => 71.2],
        ['month' => 'T1/25', 'rate' => 73.5],
        ['month' => 'T2/25', 'rate' => 68.9],
        ['month' => 'T3/25', 'rate' => 74.1],
        ['month' => 'T4/25', 'rate' => 76.8],
        ['month' => 'T5/25', 'rate' => 72.3],
    ];

    $retentionBarConfigs = [
        ['key' => 'rate', 'name' => 'Tỷ lệ quay lại', 'color' => '#3B82F6'],
    ];

    $latestRetention = 72.3;
    $prevRetention = 76.8;
    $retentionTrend = $latestRetention - $prevRetention;

    function roleClass($role) {
        return match ($role) {
            'Thợ chính' => 'role-badge role-badge--main',
            'Thợ phụ' => 'role-badge role-badge--assistant',
            'Lễ tân' => 'role-badge role-badge--reception',
            default => 'role-badge',
        };
    }

    function upsellClass($value) {
        if ($value >= 70) {
            return 'upsell-fill upsell-fill--good';
        }

        if ($value >= 50) {
            return 'upsell-fill upsell-fill--warning';
        }

        return 'upsell-fill upsell-fill--danger';
    }
@endphp

<div
    class="branch-revenue-page"
    id="managerRevenueReportPage"
    data-target-progress-url="{{ route('api.dashboard.manager.branches.revenue-report.target-progress', ['branchId' => $managerBranchId]) }}"
    data-revenue-comparison-url="{{ route('api.dashboard.manager.branches.revenue-report.revenue-comparison', ['branchId' => $managerBranchId]) }}"
    data-service-mix-url="{{ route('api.dashboard.manager.branches.revenue-report.service-mix', ['branchId' => $managerBranchId]) }}"
    data-aov-url="{{ route('api.dashboard.manager.branches.revenue-report.aov', ['branchId' => $managerBranchId]) }}"
    data-employee-performance-url="{{ route('api.dashboard.manager.branches.revenue-report.employee-performance', ['branchId' => $managerBranchId]) }}"
    data-customer-retention-url="{{ route('api.dashboard.manager.branches.revenue-report.customer-retention', ['branchId' => $managerBranchId]) }}"
>

    <x-global-control-panel
        title="Chi nhánh Quận 1"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
        :export-url="route('manager.reports.export')"
    />

    <div class="branch-revenue-grid">

        {{-- 1. GROWTH TRACKING --}}
        <section class="revenue-card revenue-card--growth">
            <div class="growth-header">
                <div>
                    <div class="section-subtitle">
                        <span></span>
                        Tiến độ mục tiêu tháng
                    </div>

                    <h2>
                        <span data-target-current>{{ number_format($currentRevenue, 0, ',', '.') }}</span>
                        <small>/ <span data-target-month>{{ number_format($targetRevenue, 0, ',', '.') }}</span>tr</small>
                    </h2>
                </div>

                <div
                    class="{{ $progress >= 80 ? 'progress-badge progress-badge--good' : ($progress >= 50 ? 'progress-badge progress-badge--warning' : 'progress-badge progress-badge--danger') }}"
                    data-target-progress-badge
                >
                    <span data-target-progress>{{ number_format($progress, 1) }}%</span>
                </div>
            </div>

            <div class="progress-section">
                <div class="progress-track">
                    <div
                        class="{{ $progress >= 80 ? 'progress-fill progress-fill--good' : ($progress >= 50 ? 'progress-fill progress-fill--warning' : 'progress-fill progress-fill--danger') }}"
                        style="width: {{ $progress }}%;"
                        data-target-progress-fill
                    ></div>
                </div>

                <div class="progress-stats">
                    <span>
                        Còn thiếu:
                        <strong data-target-remaining>{{ number_format($remaining, 0, ',', '.') }}tr</strong>
                    </span>

                    <span>
                        Cần/ngày:
                        <strong class="orange" data-target-daily-needed>{{ number_format($dailyNeeded, 1) }}tr</strong>
                        (<span data-target-days-left>{{ $daysLeft }}</span> ngày còn lại)
                    </span>
                </div>

                <div class="progress-badge progress-badge--warning" data-target-warning-badge>
                    <span data-target-warning>Đang cập nhật tiến độ</span>
                </div>
            </div>

            <div class="chart-title-row">
                <span>So sánh doanh thu</span>
            </div>

            <div class="chart-area">
                <x-chart.line
                    id="managerRevenueComparisonChart"
                    :data="$revenueTrendData"
                    xAxisKey="label"
                    :lineConfigs="$revenueLineConfigs"
                    yAxisFormatter="raw"
                    height="280px"
                />
            </div>
        </section>


        {{-- 2. SERVICE MIX --}}
        <section class="revenue-card revenue-card--service">
            <div class="section-header">
                <div class="section-subtitle">
                    <span></span>
                    Cơ cấu dịch vụ
                </div>

                <h2>Service Mix & AOV</h2>
            </div>

            <div class="service-insight-row">
                <div class="service-insight-card service-insight-card--hotel">
                    <div class="service-insight-label">🏨 Hotel</div>
                    <strong data-hotel-share>{{ number_format($hotelShare, 1) }}%</strong>
                    <small>Chó + Mèo</small>
                </div>

                <div class="service-insight-card service-insight-card--spa">
                    <div class="service-insight-label">✂️ Spa</div>
                    <strong data-spa-share>{{ number_format($spaShare, 1) }}%</strong>
                    <small>Grooming</small>
                </div>
            </div>

            <div class="service-chart-layout">
                <div class="service-chart">
                    <x-chart.pie
                        id="managerServiceMixChart"
                        :data="$serviceData"
                        nameKey="name"
                        dataKey="value"
                        :colors="$serviceColors"
                        height="260px"
                    />
                </div>

                <div class="service-legend" data-service-mix-legend>
                    @foreach ($serviceData as $service)
                        <div class="service-legend-item">
                            <div>
                                <span style="background: {{ $service['color'] }};"></span>
                                {{ $service['icon'] }} {{ $service['name'] }}
                            </div>

                            <strong style="color: {{ $service['color'] }};">
                                {{ $service['value'] }}%
                            </strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="aov-section">
                <h3>Giá trị đơn hàng trung bình (AOV)</h3>

                <div class="aov-grid">
                    @foreach ($aovData as $item)
                        <div
                            class="{{ $item['highlight'] ? 'aov-card aov-card--highlight' : 'aov-card' }}"
                            data-aov-card="{{ $item['key'] }}"
                        >
                            <div class="aov-label">{{ $item['label'] }}</div>
                            <div class="aov-value" data-aov-value="{{ $item['key'] }}">{{ $item['value'] }}</div>

                            @if ($item['change'])
                                <div class="aov-change" data-aov-change>▲ {{ $item['change'] }} so tháng trước</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>


        {{-- 3. STAFF PERFORMANCE --}}
        <section class="revenue-card revenue-card--staff">
            <div class="staff-header">
                <div>
                    <div class="section-subtitle">
                        <span></span>
                        Hiệu suất nhân sự
                    </div>

                    <h2>Xếp hạng năng suất & Upsell</h2>
                </div>

                <div class="staff-toggle">
                    <button type="button" class="active">Doanh thu</button>
                    <button type="button">Upsell</button>
                </div>
            </div>

            <div class="staff-warning">
                ⚠️ Phát hiện <strong>dấu hiệu bất thường</strong>. Kiểm tra ngay nhân viên được đánh dấu đỏ.
            </div>

            <div class="staff-table-header">
                <span>Nhân viên</span>
                <span>DT (tr)</span>
                <span>Upsell</span>
            </div>

            <div class="staff-table-body">
                @foreach ($staffData as $index => $staff)
                    @php
                        $revenuePercent = $topRevenue > 0 ? ($staff['revenue'] / $topRevenue) * 100 : 0;
                    @endphp

                    <div class="{{ $staff['alert'] ? 'staff-row staff-row--alert' : ($index === 0 ? 'staff-row staff-row--top' : 'staff-row') }}">
                        <div class="staff-info">
                            <div class="{{ $index === 0 ? 'staff-rank staff-rank--top' : 'staff-rank' }}">
                                {{ $index + 1 }}

                                @if ($staff['alert'])
                                    <span class="staff-alert-dot"></span>
                                @endif
                            </div>

                            <div>
                                <div class="staff-name">{{ $staff['name'] }}</div>
                                <div class="{{ roleClass($staff['role']) }}">
                                    {{ $staff['role'] }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="staff-revenue">{{ $staff['revenue'] }}tr</div>
                            <div class="staff-revenue-track">
                                <div style="width: {{ $revenuePercent }}%;"></div>
                            </div>
                        </div>

                        <div>
                            <div class="upsell-value">{{ $staff['upsellRate'] }}%</div>
                            <div class="upsell-track">
                                <div class="{{ upsellClass($staff['upsellRate']) }}" style="width: {{ $staff['upsellRate'] }}%;"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>


        {{-- 4. CUSTOMER ANALYTICS --}}
        <section class="revenue-card revenue-card--customer">
            <div class="section-header">
                <div class="section-subtitle">
                    <span></span>
                    Hiệu suất duy trì
                </div>

                <h2>Khách hàng thân quen</h2>
            </div>

            <div class="customer-content">
                <div class="customer-chart-card">
                    <div class="customer-metric-label">Tỷ lệ quay lại</div>

                    <div class="customer-metric-row">
                        <span class="{{ $retentionTrend >= 0 ? 'customer-rate customer-rate--positive' : 'customer-rate customer-rate--negative' }}">
                            {{ $latestRetention }}%
                        </span>

                        <span class="{{ $retentionTrend >= 0 ? 'customer-trend customer-trend--positive' : 'customer-trend customer-trend--negative' }}">
                            {{ $retentionTrend >= 0 ? '▲' : '▼' }}
                            {{ number_format(abs($retentionTrend), 1) }}%
                        </span>
                    </div>

                    <div class="customer-chart">
                        <x-chart.bar
                            :data="$retentionData"
                            xAxisKey="month"
                            :barConfigs="$retentionBarConfigs"
                            yAxisFormatter="raw"
                            height="250px"
                        />
                    </div>
                </div>

                <div class="customer-stats-column">
                    <div class="customer-stat-card">
                        <div>KH mới (Tháng này)</div>
                        <strong>+ 214</strong>
                    </div>

                    <div class="customer-stat-card">
                        <div>KH trung thành</div>
                        <strong class="secondary">572</strong>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/branch-revenue-reports.js') }}?v={{ time() }}"></script>
@endpush
