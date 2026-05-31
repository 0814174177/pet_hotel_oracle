@extends('layouts.ceo')

@section('title', 'Tổng quan hoạt động')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/dashboard-overview.css') }}?v={{ time() }}">
@endpush

@section('content')
<div
    class="dashboard-overview-page"
    id="ceoDashboard"
    data-current-occupancy-url="{{ route('api.dashboard.ceo.current-hotel-occupancy') }}"
    data-occupancy-rate-url="{{ route('api.dashboard.ceo.occupancy-rate') }}"
    data-revpar-url="{{ route('api.dashboard.ceo.revpar') }}"
    data-customer-trend-url="{{ route('api.dashboard.ceo.customer-trend') }}"
    data-total-revenue-url="{{ route('api.dashboard.ceo.revenue') }}"
    data-estimated-cogs-url="{{ route('api.dashboard.ceo.estimated-cogs') }}"
    data-cost-structure-url="{{ route('api.dashboard.ceo.cost-structure') }}"
    data-revenue-mix-url="{{ route('api.dashboard.ceo.revenue-mix') }}"
    data-revenue-cogs-trend-url="{{ route('api.dashboard.ceo.revenue-and-cogs-trend') }}"
    data-branch-revenue-url="{{ route('api.dashboard.ceo.branch-revenue') }}"
    data-branch-ranking-url="{{ route('api.dashboard.ceo.branch-ranking') }}"
    data-top-used-services-url="{{ route('api.dashboard.ceo.top-used-services') }}"
    data-risk-alerts-url="{{ route('api.dashboard.ceo.risk-alerts') }}"
>
    <x-global-control-panel
        data-dashboard-controls
        title="Tổng quan hoạt động"
        :period="match (request('filter_type', 'month')) {
            'day' => 'ngày',
            'year' => 'năm',
            default => 'tháng',
        }"
        lastUpdate="Đang tải dữ liệu..."
    />

    <p class="dashboard-status" id="dashboardStatus" role="status" aria-live="polite">
        Đang tải dữ liệu báo cáo...
    </p>

    {{-- 1. TỔNG QUAN VẬN HÀNH --}}
    <section class="dashboard-section">
        <div class="dashboard-section__header">
            <h2>1. Tổng quan Vận hành</h2>
            <p>Đánh giá công suất làm việc và sự ổn định của toàn hệ thống</p>
        </div>

        <div class="dashboard-metrics-row dashboard-metrics-row--three">
            <div class="kpi-card-wrapper" id="occupancyRateKpi">
                <div class="kpi-card">
                    <div class="kpi-card__content">
                        <p class="kpi-card__title">Tỷ lệ lấp đầy phòng Hotel</p>
                        <h3 class="kpi-card__value" data-kpi-value>--</h3>
                        <p class="kpi-card__trend" data-kpi-trend>
                            <span class="kpi-card__arrow" data-kpi-arrow></span>
                            <span class="kpi-card__trend-value" data-kpi-trend-value>--</span>
                            <span class="kpi-card__period-label">so với kỳ trước</span>
                        </p>
                        <p class="dashboard-kpi-detail" data-kpi-detail>Đang tải số phòng...</p>
                    </div>
                </div>
            </div>

            <div class="kpi-card-wrapper" id="revparKpi">
                <div class="kpi-card">
                    <div class="kpi-card__content">
                        <p class="kpi-card__title">Doanh thu trên mỗi phòng (RevPAR)</p>
                        <h3 class="kpi-card__value" data-kpi-value>--</h3>
                        <p class="kpi-card__trend" data-kpi-trend>
                            <span class="kpi-card__arrow" data-kpi-arrow></span>
                            <span class="kpi-card__trend-value" data-kpi-trend-value>--</span>
                            <span class="kpi-card__period-label">so với kỳ trước</span>
                        </p>
                        <p class="dashboard-kpi-detail" data-kpi-detail>Đang tải doanh thu phòng...</p>
                    </div>
                </div>
            </div>

            <div class="alert-box-wrapper" id="dashboardOperationalAlert">
                <div class="alert-box alert-box--warning">
                    <h4 class="alert-box__header">
                        <span class="alert-box__icon" aria-hidden="true">!</span>
                        <span data-alert-title>Đang tải cảnh báo vận hành</span>
                    </h4>
                    <p class="alert-box__message" data-alert-message>Đang tổng hợp dữ liệu rủi ro...</p>
                </div>
            </div>
        </div>

        <div class="dashboard-chart-stack">
            <div class="dashboard-chart-card">
                <h3>Xu hướng khách hàng theo thời gian</h3>
                <div class="dashboard-chart-area">
                    <canvas id="customerTrendChart" aria-label="Biểu đồ xu hướng khách hàng"></canvas>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. TỔNG QUAN TÀI CHÍNH --}}
    <section class="dashboard-section">
        <div class="dashboard-section__header">
            <h2>2. Tổng quan Tài chính</h2>
            <p>Kiểm soát dòng tiền vào và ra toàn chuỗi</p>
        </div>

        <div class="dashboard-metrics-row dashboard-metrics-row--two">
            <div class="kpi-card-wrapper" id="totalRevenueKpi">
                <div class="kpi-card">
                    <div class="kpi-card__content">
                        <p class="kpi-card__title">Tổng doanh thu toàn chuỗi</p>
                        <h3 class="kpi-card__value" data-kpi-value>--</h3>
                        <p class="kpi-card__trend" data-kpi-trend>
                            <span class="kpi-card__arrow" data-kpi-arrow></span>
                            <span class="kpi-card__trend-value" data-kpi-trend-value>--</span>
                            <span class="kpi-card__period-label">so với kỳ trước</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="kpi-card-wrapper" id="estimatedCogsKpi">
                <div class="kpi-card">
                    <div class="kpi-card__content">
                        <p class="kpi-card__title">Chi phí vật tư tiêu hao ước tính (COGS)</p>
                        <h3 class="kpi-card__value" data-kpi-value>--</h3>
                        <p class="kpi-card__trend" data-kpi-trend>
                            <span class="kpi-card__arrow" data-kpi-arrow></span>
                            <span class="kpi-card__trend-value" data-kpi-trend-value>--</span>
                            <span class="kpi-card__period-label">so với kỳ trước</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-chart-grid dashboard-chart-grid--two">
            <div class="dashboard-chart-card">
                <h3>Tỷ trọng doanh thu Grooming & Spa và Hotel</h3>
                <div class="dashboard-chart-area">
                    <canvas id="revenueMixChart" aria-label="Biểu đồ tỷ trọng doanh thu"></canvas>
                </div>
            </div>

            <div class="dashboard-chart-card">
                <h3>Cơ cấu chi phí ước tính</h3>
                <div class="dashboard-chart-area">
                    <canvas id="costStructureChart" aria-label="Biểu đồ cơ cấu chi phí ước tính"></canvas>
                </div>
            </div>

            <div class="dashboard-chart-card">
                <h3>Xu hướng Doanh thu và Chi phí (COGS)</h3>
                <div class="dashboard-chart-area">
                    <canvas id="revenueCogsTrendChart" aria-label="Biểu đồ doanh thu và chi phí"></canvas>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. PHÂN TÍCH CHIẾN LƯỢC VÀ RỦI RO --}}
    <section class="dashboard-section">
        <div class="dashboard-section__header">
            <h2>3. Phân tích Chiến lược & Quản trị Rủi ro</h2>
            <p>Xác thực thông tin, chống thất thoát và tối ưu chuỗi cung ứng</p>
        </div>

        <div class="dashboard-risk-layout">
            <div class="dashboard-chart-card">
                <h3>Doanh thu theo từng chi nhánh (Triệu VNĐ)</h3>
                <div class="dashboard-chart-area">
                    <canvas id="branchRevenueChart" aria-label="Biểu đồ doanh thu theo chi nhánh"></canvas>
                </div>
            </div>

            <div class="dashboard-leaderboard-section">
                <h3 class="dashboard-leaderboard-title">Bảng Xếp Hạng</h3>

                <div class="dashboard-board-card dashboard-board-card--blue">
                    <h4>Top 3 chi nhánh có doanh thu cao nhất</h4>
                    <ul id="branchRankingList">
                        <li class="dashboard-board-card__placeholder">Đang tải dữ liệu...</li>
                    </ul>
                </div>

                <div class="dashboard-board-card dashboard-board-card--purple">
                    <h4>Top 5 dịch vụ được sử dụng nhiều nhất</h4>
                    <ul id="topUsedServicesList">
                        <li class="dashboard-board-card__placeholder">Đang tải dữ liệu...</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="dashboard-alert-section">
            <h3>Cảnh báo quản trị rủi ro</h3>
            <div class="dashboard-alert-list" id="dashboardRiskAlerts">
                <div class="dashboard-alert-empty">Đang tải cảnh báo...</div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/ceo/dashboard-overview.js') }}?v={{ time() }}"></script>
@endpush
