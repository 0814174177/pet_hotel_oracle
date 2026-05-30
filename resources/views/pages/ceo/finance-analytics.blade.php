@extends('layouts.ceo')

@section('title', 'Phân tích Tài chính Tổng thể')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/finance-analytics.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $period = 'tháng';

    /*
    |--------------------------------------------------------------------------
    | 1. DATA MẪU: P&L DASHBOARD
    |--------------------------------------------------------------------------
    */
    $plKpis = [
        [
            'title' => 'Tổng Doanh thu (Gross Revenue)',
            'value' => number_format(2500000000, 0, ',', '.') . ' ₫',
            'trend' => '12.5%',
            'isPositive' => true,
            'period' => $period,
        ],
        [
            'title' => 'Tổng Chi phí (Vận hành & Nhập kho)',
            'value' => number_format(1800000000, 0, ',', '.') . ' ₫',
            'trend' => '4.2%',
            'isPositive' => false,
            'period' => $period,
        ],
        [
            'title' => 'Lợi Nhuận Ròng (Net Profit)',
            'value' => number_format(700000000, 0, ',', '.') . ' ₫',
            'trend' => '15.8%',
            'isPositive' => true,
            'period' => $period,
        ],
    ];

    $plChartData = [
        ['time' => 'T1', 'revenue' => 400, 'cost' => 250],
        ['time' => 'T2', 'revenue' => 450, 'cost' => 280],
        ['time' => 'T3', 'revenue' => 600, 'cost' => 320],
        ['time' => 'T4', 'revenue' => 550, 'cost' => 300],
        ['time' => 'T5', 'revenue' => 700, 'cost' => 350],
        ['time' => 'T6', 'revenue' => 850, 'cost' => 400],
    ];

    $plLineConfigs = [
        ['key' => 'revenue', 'name' => 'Tổng Doanh thu', 'color' => '#0ea5e9'],
        ['key' => 'cost', 'name' => 'Tổng Chi phí (COGS + Opex)', 'color' => '#ef4444'],
    ];

    /*
    |--------------------------------------------------------------------------
    | 2. DATA MẪU: UNIT ECONOMICS
    |--------------------------------------------------------------------------
    */
    $unitPieData = [
        ['name' => 'Spa & Grooming', 'value' => 45],
        ['name' => 'Lưu trú (Hotel)', 'value' => 55],
    ];

    $unitPieColors = [
        '#0ea5e9',
        '#8b5cf6',
    ];

    $lowMarginServices = [
        [
            'id' => 'SPA-04',
            'name' => 'Tắm chó lông dài (Lớn)',
            'category' => 'Grooming',
            'price' => 350000,
            'cogs' => 280000,
            'margin' => 20,
        ],
        [
            'id' => 'HTL-02',
            'name' => 'Lưu chuồng tiêu chuẩn',
            'category' => 'Hotel',
            'price' => 150000,
            'cogs' => 110000,
            'margin' => 26,
        ],
        [
            'id' => 'SPA-09',
            'name' => 'Cắt tỉa tạo kiểu Poodle',
            'category' => 'Grooming',
            'price' => 400000,
            'cogs' => 240000,
            'margin' => 40,
        ],
        [
            'id' => 'HTL-05',
            'name' => 'Lưu chuồng VIP (Có Camera)',
            'category' => 'Hotel',
            'price' => 500000,
            'cogs' => 150000,
            'margin' => 70,
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | 3. DATA MẪU: CUSTOMER FINANCIALS
    |--------------------------------------------------------------------------
    */
    $customerKpis = [
        [
            'title' => 'CAC - Chi phí thu hút 1 khách',
            'value' => number_format(150000, 0, ',', '.') . ' ₫',
            'trend' => 'Giảm 6.8%',
            'isPositive' => true,
            'period' => $period,
        ],
        [
            'title' => 'CLV - Giá trị vòng đời khách hàng',
            'value' => number_format(4500000, 0, ',', '.') . ' ₫',
            'trend' => 'Tăng 11.2%',
            'isPositive' => true,
            'period' => $period,
        ],
        [
            'title' => 'Tỷ lệ CLV / CAC',
            'value' => '30x',
            'trend' => 'Hiệu quả tài chính tốt',
            'isPositive' => true,
            'period' => $period,
        ],
    ];

    $customerTrendData = [
        ['month' => 'T1', 'newCustomers' => 120, 'returnCustomers' => 300],
        ['month' => 'T2', 'newCustomers' => 150, 'returnCustomers' => 320],
        ['month' => 'T3', 'newCustomers' => 90, 'returnCustomers' => 280],
        ['month' => 'T4', 'newCustomers' => 180, 'returnCustomers' => 360],
        ['month' => 'T5', 'newCustomers' => 210, 'returnCustomers' => 410],
        ['month' => 'T6', 'newCustomers' => 190, 'returnCustomers' => 430],
    ];

    $customerLineConfigs = [
        ['key' => 'newCustomers', 'name' => 'Khách mới', 'color' => '#f59e0b'],
        ['key' => 'returnCustomers', 'name' => 'Khách quay lại', 'color' => '#10b981'],
    ];

    /*
    |--------------------------------------------------------------------------
    | 4. DATA MẪU: COST OPTIMIZATION
    |--------------------------------------------------------------------------
    */
    $lossAlerts = [
        [
            'id' => 'AL-01',
            'branch' => 'Chi nhánh Quận 1',
            'title' => 'Nghi vấn thất thoát: Sữa tắm SOS Mềm mượt',
            'description' => 'Lượng xuất kho thực tế cao hơn 25% so với định mức của 120 dịch vụ tắm Spa đã bán trong tuần.',
            'estimatedLoss' => 1250000,
            'severity' => 'danger',
        ],
        [
            'id' => 'AL-02',
            'branch' => 'Chi nhánh Quận 7',
            'title' => 'Hao hụt bất thường: Cát vệ sinh Clean Cat',
            'description' => 'Tỷ lệ hao hụt đạt mức 18% (Vượt mức cho phép 5%). Cần kiểm tra lại quy trình dọn khay vệ sinh tại Hotel.',
            'estimatedLoss' => 840000,
            'severity' => 'warning',
        ],
    ];

    $supplierDebts = [
        [
            'id' => 'INV-2026-089',
            'supplier' => 'Công ty Cổ phần PetCare VN',
            'amount' => 45000000,
            'daysOverdue' => 15,
            'status' => 'overdue',
        ],
        [
            'id' => 'INV-2026-092',
            'supplier' => 'Đại lý Cát Vệ Sinh Sài Gòn',
            'amount' => 12500000,
            'daysOverdue' => 3,
            'status' => 'overdue',
        ],
        [
            'id' => 'INV-2026-105',
            'supplier' => 'Xưởng nệm thú cưng Happy',
            'amount' => 8000000,
            'daysOverdue' => -2,
            'status' => 'duesoon',
        ],
    ];

    function financeMoney($amount) {
        return number_format($amount, 0, ',', '.') . ' ₫';
    }

    function marginClass($margin) {
        if ($margin < 30) {
            return 'finance-margin finance-margin--bad';
        }

        if ($margin <= 50) {
            return 'finance-margin finance-margin--ok';
        }

        return 'finance-margin finance-margin--good';
    }
@endphp

<div class="finance-page">

    <x-global-control-panel
        title="Phân tích Tài chính Tổng thể"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
    />

    {{-- 1. P&L DASHBOARD --}}
    <section class="finance-section">
        <div class="finance-section__header">
            <h2>1. Báo cáo Lãi/Lỗ Tổng thể</h2>
            <p>Theo dõi doanh thu, chi phí và lợi nhuận ròng của toàn chuỗi.</p>
        </div>

        <div class="finance-kpi-grid finance-kpi-grid--three">
            @foreach ($plKpis as $item)
                <x-kpi-card
                    :title="$item['title']"
                    :value="$item['value']"
                    :trend="$item['trend']"
                    :isPositive="$item['isPositive']"
                    :period="$item['period']"
                    :icon="null"
                />
            @endforeach
        </div>

        <div class="finance-card">
            <h3>Biểu đồ so sánh Doanh thu và Chi phí theo {{ $period }}</h3>

            <div class="finance-chart-area">
                <x-chart.line
                    :data="$plChartData"
                    xAxisKey="time"
                    :lineConfigs="$plLineConfigs"
                    yAxisFormatter="raw"
                    height="320px"
                />
            </div>
        </div>
    </section>


    {{-- 2. UNIT ECONOMICS --}}
    <section class="finance-section">
        <div class="finance-section__header">
            <h2>2. Phân tích Biên lợi nhuận Dịch vụ</h2>
            <p>Đánh giá tỷ trọng doanh thu và biên lợi nhuận của từng nhóm dịch vụ.</p>
        </div>

        <div class="finance-two-column">
            <div class="finance-card">
                <h3>Tỷ trọng Doanh thu (Grooming vs Hotel)</h3>

                <div class="finance-chart-area">
                    <x-chart.pie
                        :data="$unitPieData"
                        nameKey="name"
                        dataKey="value"
                        :colors="$unitPieColors"
                        height="320px"
                    />
                </div>
            </div>

            <div class="finance-card">
                <h3>Phân tích Lãi gộp</h3>

                <div class="finance-table-wrapper">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th>Dịch vụ</th>
                                <th>Giá bán</th>
                                <th>Giá vốn</th>
                                <th class="text-center">Margin</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($lowMarginServices as $service)
                                <tr>
                                    <td>
                                        <div class="finance-service-name">{{ $service['name'] }}</div>
                                        <div class="finance-service-meta">
                                            {{ $service['id'] }} • Phân nhóm: {{ $service['category'] }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="finance-money">{{ financeMoney($service['price']) }}</span>
                                    </td>

                                    <td>
                                        <span class="finance-money finance-money--muted">{{ financeMoney($service['cogs']) }}</span>
                                    </td>

                                    <td class="text-center">
                                        <span class="{{ marginClass($service['margin']) }}">
                                            {{ $service['margin'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>


    {{-- 3. CUSTOMER FINANCIALS --}}
    <section class="finance-section">
        <div class="finance-section__header">
            <h2>3. Phân tích Dòng tiền Khách hàng</h2>
            <p>Đo lường hiệu quả thu hút khách hàng và giá trị vòng đời khách hàng.</p>
        </div>

        <div class="finance-kpi-grid finance-kpi-grid--three">
            @foreach ($customerKpis as $item)
                <x-kpi-card
                    :title="$item['title']"
                    :value="$item['value']"
                    :trend="$item['trend']"
                    :isPositive="$item['isPositive']"
                    :period="$item['period']"
                    :icon="null"
                />
            @endforeach
        </div>

        <div class="finance-card">
            <h3>Xu hướng Khách mới và Khách quay lại</h3>

            <div class="finance-chart-area">
                <x-chart.line
                    :data="$customerTrendData"
                    xAxisKey="month"
                    :lineConfigs="$customerLineConfigs"
                    yAxisFormatter="raw"
                    height="320px"
                />
            </div>
        </div>
    </section>


    {{-- 4. COST OPTIMIZATION --}}
    <section class="finance-section">
        <div class="finance-section__header">
            <h2>4. Báo cáo Thất thoát & Tối ưu Chi phí</h2>
            <p>Phát hiện hao hụt vật tư, gian lận nội bộ và công nợ nhà cung cấp.</p>
        </div>

        <div class="finance-two-column">
            <div class="finance-card">
                <div class="finance-card-title-row">
                    <h3>🚨 Cảnh báo Thất thoát & Gian lận nội bộ</h3>
                    <span>{{ count($lossAlerts) }} cảnh báo chưa xử lý</span>
                </div>

                <div class="finance-alert-list">
                    @foreach ($lossAlerts as $alert)
                        <div class="finance-loss-alert {{ $alert['severity'] === 'warning' ? 'finance-loss-alert--warning' : '' }}">
                            <div class="finance-loss-alert__icon">
                                {{ $alert['severity'] === 'danger' ? '⛔' : '⚠️' }}
                            </div>

                            <div class="finance-loss-alert__content">
                                <h4>[{{ $alert['branch'] }}] {{ $alert['title'] }}</h4>
                                <p>{{ $alert['description'] }}</p>
                                <div>
                                    Thiệt hại ước tính: {{ financeMoney($alert['estimatedLoss']) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="finance-card">
                <h3>💸 Cảnh báo Công nợ Nhà cung cấp</h3>

                <div class="finance-table-wrapper">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th>Nhà cung cấp / Mã phiếu</th>
                                <th>Số tiền nợ</th>
                                <th>Tình trạng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($supplierDebts as $debt)
                                <tr>
                                    <td>
                                        <div class="finance-service-name">{{ $debt['supplier'] }}</div>
                                        <div class="finance-service-meta">{{ $debt['id'] }}</div>
                                    </td>

                                    <td>
                                        <span class="finance-money">{{ financeMoney($debt['amount']) }}</span>
                                    </td>

                                    <td>
                                        @if ($debt['status'] === 'overdue')
                                            <span class="finance-debt-badge finance-debt-badge--overdue">
                                                Quá hạn {{ $debt['daysOverdue'] }} ngày
                                            </span>
                                        @else
                                            <span class="finance-debt-badge finance-debt-badge--duesoon">
                                                Đến hạn sau {{ abs($debt['daysOverdue']) }} ngày
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <button type="button" class="finance-pay-btn">Thanh toán</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
