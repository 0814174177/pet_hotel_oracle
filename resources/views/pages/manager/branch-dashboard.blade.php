@extends('layouts.manager')

@section('title', 'Dashboard Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-dashboard.css') }}?v={{ time() }}">
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
    $period = 'kỳ lọc';

    /*
    |--------------------------------------------------------------------------
    | KPI DATA
    |--------------------------------------------------------------------------
    */
    $kpiData = [
        [
            'key' => 'check-in-schedule',
            'title' => 'Lịch Check-in',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => true,
            'period' => $period,
        ],
        [
            'key' => 'check-out-schedule',
            'title' => 'Lịch Check-out',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => true,
            'period' => $period,
        ],
        [
            'key' => 'grooming-schedule',
            'title' => 'Lịch Spa/Grooming',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => false,
            'period' => $period,
        ],
        [
            'key' => 'walk-in-available-rooms',
            'title' => 'Phòng trống (Walk-in)',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => false,
            'period' => $period,
        ],
    ];
@endphp

<div
    class="manager-branch-dashboard"
    id="managerBranchDashboard"
    data-overview-url="{{ route('api.dashboard.manager.branches.overview', ['branchId' => $managerBranchId]) }}"
    data-inventory-warning-url="{{ route('api.dashboard.manager.branches.inventory-warning', ['branchId' => $managerBranchId]) }}"
    data-health-warning-url="{{ route('api.dashboard.manager.branches.health-warning', ['branchId' => $managerBranchId]) }}"
    data-financial-risk-warning-url="{{ route('api.dashboard.manager.branches.financial-risk-warning', ['branchId' => $managerBranchId]) }}"
    data-late-cancelled-bookings-url="{{ route('api.dashboard.manager.branches.late-cancelled-bookings', ['branchId' => $managerBranchId]) }}"
    data-top-revenue-services-url="{{ route('api.dashboard.manager.branches.top-revenue-services', ['branchId' => $managerBranchId]) }}"
    data-revenue-structure-url="{{ route('api.dashboard.manager.branches.revenue-structure', ['branchId' => $managerBranchId]) }}"
    data-unpaid-invoices-url="{{ route('api.dashboard.manager.branches.unpaid-invoices', ['branchId' => $managerBranchId]) }}"
>

    <x-global-control-panel
        :title="$managerPanelTitle"
    />

    <div class="manager-dashboard-main">

        {{-- DAILY KPI CARDS --}}
        <section class="manager-kpi-grid">
            @foreach ($kpiData as $item)
                <x-kpi-card
                    data-kpi="{{ $item['key'] }}"
                    :title="$item['title']"
                    :value="$item['value']"
                    :trend="$item['trend']"
                    :isPositive="$item['isPositive']"
                    :period="$item['period']"
                    :icon="null"
                />
            @endforeach
        </section>


        {{-- URGENT ALERTS --}}
        <section class="manager-section">
            <h2 class="manager-section-title">Bảng Công Việc Khẩn Cấp (To-Do List)</h2>

            <div class="urgent-alerts-wrapper">

                {{-- Health alerts --}}
                <div class="safe-state-card" data-health-warning-card data-severity="green">
                    <div class="health-warning-summary" data-health-warning-summary data-severity="green">
                        <span><strong data-health-warning-count>Đang tải...</strong> pet cần theo dõi</span>
                    </div>
                    <span data-health-warning-text>Đang tải cảnh báo y tế...</span>
                </div>

                {{-- Inventory alerts --}}
                <div class="safe-state-card" data-inventory-warning-card data-severity="green">
                    <div class="inventory-warning-summary" data-inventory-warning-summary data-severity="green">
                        <span><strong data-inventory-out-of-stock-count>Đang tải...</strong> vật tư hết hàng</span>
                        <span><strong data-inventory-low-stock-count>Đang tải...</strong> vật tư sắp hết</span>
                    </div>
                    <span data-inventory-warning-text>Đang tải cảnh báo tồn kho...</span>
                </div>

                {{-- Late cancelled bookings --}}
                <div class="manager-alert-card" data-late-cancelled-card>
                    <h3 class="manager-alert-title manager-alert-title--orange">
                        Booking h&#7911;y s&#225;t gi&#7901;:
                        <span data-late-cancelled-total>&#272;ang t&#7843;i...</span> ca
                    </h3>

                    <div class="manager-grid-table">
                        <div class="manager-grid-header manager-late-cancel-header">
                            <div>M&#227; Booking</div>
                            <div>Kh&#225;ch h&#224;ng</div>
                            <div>Check-in d&#7921; ki&#7871;n</div>
                            <div>Th&#7901;i &#273;i&#7875;m h&#7911;y</div>
                            <div class="text-right">Gi&#7901; tr&#432;&#7899;c check-in</div>
                            <div class="text-right">T&#7893;ng ti&#7873;n &#273;&#417;n</div>
                            <div>C&#7843;nh b&#225;o</div>
                        </div>

                        <div class="manager-grid-body" data-late-cancelled-body>
                            <div class="manager-grid-row manager-late-cancel-row">
                                <div class="cell-note">&#272;ang t&#7843;i...</div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                    </div>

                    <div class="empty-message" data-late-cancelled-empty hidden>
                        Kh&#244;ng c&#243; booking h&#7911;y s&#225;t gi&#7901; trong k&#7923; n&#224;y.
                    </div>
                </div>

            </div>
        </section>


        {{-- FINANCIAL RISK REPORT --}}
        <section class="manager-risk-section" data-financial-risk-warning-card data-severity="yellow">
            <h2 class="manager-section-title">
                Radar Cảnh Báo & Rủi Ro Tài Chính
            </h2>

            <div class="risk-summary-grid">
                <div class="risk-summary-card" data-financial-risk-count-card>
                    <p>Tổng số ca Hủy (Trong kỳ)</p>
                    <strong><span data-financial-risk-cancelled-count>Đang tải...</span> <span>ca</span></strong>
                </div>

                <div class="risk-summary-card" data-financial-risk-amount-card>
                    <p>Tổng Giá Trị Thất Thoát</p>
                    <strong class="text-red" data-financial-risk-lost-amount>Đang tải...</strong>
                    <small>* Dựa trên grand_total của hóa đơn bị hủy</small>
                </div>
            </div>

            <div class="financial-risk-warning" data-financial-risk-warning-text data-severity="yellow">
                Đang tải cảnh báo rủi ro tài chính...
            </div>
        </section>


        {{-- OPERATIONAL REPORT --}}
        <section class="manager-operational-report">
            <div class="operational-left">

                <div class="manager-card">
                    <div class="manager-card-header">
                        <h3>Top 5 Dịch Vụ Đem Lại Doanh Thu Cao Nhất</h3>
                    </div>

                    <div class="manager-chart-wrapper">
                        <canvas id="managerTopRevenueServicesChart" aria-label="Biểu đồ top dịch vụ doanh thu cao nhất"></canvas>
                    </div>

                    <div class="manager-grid-table manager-top-service-table" data-top-revenue-services-table>
                        <div class="manager-grid-header manager-top-service-header">
                            <div>Th&#7913; h&#7841;ng</div>
                            <div>T&#234;n d&#7883;ch v&#7909;</div>
                            <div class="text-right">Doanh thu</div>
                            <div class="text-right">S&#7889; l&#432;&#7907;t s&#7917; d&#7909;ng</div>
                        </div>

                        <div class="manager-grid-body" data-top-revenue-services-body>
                            <div class="manager-grid-row manager-top-service-row">
                                <div class="cell-note">&#272;ang t&#7843;i...</div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                    </div>

                    <div class="empty-message" data-top-revenue-services-empty hidden>
                        Kh&#244;ng c&#243; d&#7919; li&#7879;u doanh thu d&#7883;ch v&#7909; trong k&#7923; n&#224;y.
                    </div>
                </div>

                <div class="manager-card" data-unpaid-invoices-card>
                    <div class="manager-card-header">
                        <h3>Sổ Ghi Công Nợ (Hóa đơn chưa thanh toán đủ)</h3>
                    </div>

                    <div class="debt-summary" data-unpaid-invoices-summary>
                        <span><strong data-unpaid-invoices-total>&#272;ang t&#7843;i...</strong> h&#243;a &#273;&#417;n c&#7847;n theo d&#245;i</span>
                        <span>T&#7893;ng c&#244;ng n&#7907;: <strong data-unpaid-invoices-total-debt>&#272;ang t&#7843;i...</strong></span>
                    </div>

                    <div class="manager-grid-table" data-unpaid-invoices-table>
                        <div class="manager-grid-header manager-debt-header">
                            <div>M&#227; KH</div>
                            <div>Kh&#225;ch h&#224;ng</div>
                            <div>S&#272;T</div>
                            <div>M&#227; &#273;&#417;n</div>
                            <div class="text-right">T&#7893;ng ti&#7873;n</div>
                            <div class="text-right">&#272;&#227; thanh to&#225;n</div>
                            <div class="text-right">C&#242;n n&#7907;</div>
                            <div>Ng&#224;y ph&#225;t sinh</div>
                            <div>C&#7843;nh b&#225;o</div>
                        </div>

                        <div class="manager-grid-body" data-unpaid-invoices-body>
                            <div class="manager-grid-row manager-debt-row">
                                <div class="cell-note">&#272;ang t&#7843;i...</div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                    </div>

                    <div class="empty-message" data-unpaid-invoices-empty hidden>
                        Kh&#244;ng c&#243; c&#244;ng n&#7907; n&#224;o c&#7847;n thu trong k&#7923; n&#224;y.
                    </div>
                </div>

            </div>

            <div class="operational-right">
                <div class="manager-card manager-card--full">
                    <div class="manager-card-header">
                        <h3>Cơ cấu Doanh thu</h3>
                    </div>

                    <div class="manager-pie-wrapper">
                        <canvas id="managerRevenueStructureChart" aria-label="Biểu đồ cơ cấu doanh thu"></canvas>
                    </div>

                    <div class="pie-summary" data-revenue-structure-summary>
                        Đang tải cơ cấu doanh thu...
                    </div>

                    <div class="manager-grid-table manager-revenue-structure-table" data-revenue-structure-table>
                        <div class="manager-grid-header manager-revenue-structure-header">
                            <div>Nh&#243;m doanh thu</div>
                            <div class="text-right">Doanh thu</div>
                            <div class="text-right">T&#7927; tr&#7885;ng</div>
                        </div>

                        <div class="manager-grid-body" data-revenue-structure-body>
                            <div class="manager-grid-row manager-revenue-structure-row">
                                <div class="cell-note">&#272;ang t&#7843;i...</div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                    </div>

                    <div class="empty-message" data-revenue-structure-empty hidden>
                        Kh&#244;ng c&#243; d&#7919; li&#7879;u c&#417; c&#7845;u doanh thu trong k&#7923; n&#224;y.
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/branch-dashboard.js') }}?v={{ time() }}"></script>
@endpush
