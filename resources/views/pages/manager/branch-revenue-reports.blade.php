@extends('layouts.manager')

@section('title', 'Báo cáo Doanh thu Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-revenue-reports.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $managerUser = auth()->user();
    $managerEmployee = $managerUser?->employee;
    $managerBranchId = $managerBranchId ?? auth()->user()?->managerBranchId();
    abort_if(blank($managerBranchId), 403, 'Manager account is not assigned to a branch.');
    $managerBranchName = $managerBranchName
        ?? $managerEmployee?->branch?->branch_name
        ?? 'Chi nhánh #'.$managerBranchId;
    $managerName = $managerEmployee?->full_name
        ?? $managerUser?->name
        ?? $managerBranchName;
    $managerPanelTitle = $managerPanelTitle
        ?? $managerName.' - Branch Manager - '.$managerBranchName;
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
        :title="$managerPanelTitle"
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
                        <span data-target-current>Đang tải...</span>
                        <small>/ <span data-target-month>--</span>tr</small>
                    </h2>
                </div>

                <div
                    class="progress-badge progress-badge--warning"
                    data-target-progress-badge
                >
                    <span data-target-progress>--</span>
                </div>
            </div>

            <div class="progress-section">
                <div class="progress-track">
                    <div
                        class="progress-fill progress-fill--warning"
                        style="width: 0%;"
                        data-target-progress-fill
                    ></div>
                </div>

                <div class="progress-stats">
                    <span>
                        Còn thiếu:
                        <strong data-target-remaining>--</strong>
                    </span>

                    <span>
                        Cần/ngày:
                        <strong class="orange" data-target-daily-needed>--</strong>
                        (<span data-target-days-left>--</span> ngày còn lại)
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
                <canvas id="managerRevenueComparisonChart" aria-label="Biểu đồ so sánh doanh thu"></canvas>
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
                    <strong data-hotel-share>Đang tải...</strong>
                    <small>Chó + Mèo</small>
                </div>

                <div class="service-insight-card service-insight-card--spa">
                    <div class="service-insight-label">✂️ Spa</div>
                    <strong data-spa-share>Đang tải...</strong>
                    <small>Grooming</small>
                </div>
            </div>

            <div class="service-chart-layout">
                <div class="service-chart">
                    <canvas id="managerServiceMixChart" aria-label="Biểu đồ cơ cấu dịch vụ"></canvas>
                </div>

                <div class="service-legend" data-service-mix-legend>
                    <div class="service-legend-empty">Đang tải cơ cấu dịch vụ...</div>
                </div>
            </div>

            <div class="aov-section">
                <h3>Giá trị đơn hàng trung bình (AOV)</h3>

                <div class="aov-grid">
                    <div class="aov-card aov-card--highlight" data-aov-card="current_aov">
                        <div class="aov-label">Kỳ này</div>
                        <div class="aov-value" data-aov-value="current_aov">Đang tải...</div>
                        <div class="aov-change" data-aov-change>Đang tải biến động...</div>
                    </div>

                    <div class="aov-card" data-aov-card="previous_aov">
                        <div class="aov-label">Kỳ trước</div>
                        <div class="aov-value" data-aov-value="previous_aov">--</div>
                    </div>

                    <div class="aov-card" data-aov-card="max_order_value">
                        <div class="aov-label">Cao nhất/đơn</div>
                        <div class="aov-value" data-aov-value="max_order_value">--</div>
                    </div>

                    <div class="aov-card" data-aov-card="current_orders">
                        <div class="aov-label">Số đơn trong kỳ</div>
                        <div class="aov-value" data-aov-value="current_orders">--</div>
                    </div>
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

            <div class="staff-warning" data-employee-performance-warning hidden>
                <strong data-employee-performance-warning-text></strong>
            </div>

            <div class="staff-table-header">
                <span>Nhân viên</span>
                <span>DT (tr)</span>
                <span>Upsell</span>
            </div>

            <div class="staff-table-body" data-employee-performance-body>
                <div class="staff-row">
                    <div class="staff-info">
                        <div class="staff-rank">--</div>
                        <div>
                            <div class="staff-name">Đang tải dữ liệu nhân sự...</div>
                            <div class="role-badge">--</div>
                        </div>
                    </div>

                    <div>
                        <div class="staff-revenue">--</div>
                        <div class="staff-revenue-track">
                            <div style="width: 0%;"></div>
                        </div>
                    </div>

                    <div>
                        <div class="upsell-value">--</div>
                        <div class="upsell-track">
                            <div class="upsell-fill" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
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
                        <span class="customer-rate" data-retention-rate>
                            Đang tải...
                        </span>

                        <span class="customer-trend" data-retention-trend>
                            --
                        </span>
                    </div>

                    <div class="customer-chart">
                        <canvas id="managerCustomerRetentionChart" aria-label="Biểu đồ tỷ lệ khách hàng quay lại"></canvas>
                    </div>
                </div>

                <div class="customer-stats-column">
                    <div class="customer-stat-card">
                        <div>KH mới trong kỳ</div>
                        <strong data-new-customers>Đang tải...</strong>
                    </div>

                    <div class="customer-stat-card">
                        <div>KH trung thành</div>
                        <strong class="secondary" data-loyal-customers>Đang tải...</strong>
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
