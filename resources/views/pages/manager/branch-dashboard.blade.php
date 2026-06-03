@extends('layouts.manager')

@section('title', 'Dashboard Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-dashboard.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $managerBranchId = auth()->user()?->employee?->branch_id ?? 1;
    $period = 'tháng';

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
            'period' => 'hôm qua',
        ],
        [
            'key' => 'check-out-schedule',
            'title' => 'Lịch Check-out',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => true,
            'period' => 'hôm qua',
        ],
        [
            'key' => 'grooming-schedule',
            'title' => 'Lịch Spa/Grooming',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => false,
            'period' => 'hôm qua',
        ],
        [
            'key' => 'walk-in-available-rooms',
            'title' => 'Phòng trống (Walk-in)',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => false,
            'period' => 'hôm qua',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | URGENT ALERTS
    |--------------------------------------------------------------------------
    */
    $healthAlerts = [
        [
            'id' => 'BK1080',
            'customer' => 'Trần Văn E',
            'pet' => 'Milo (Poodle)',
            'condition' => 'Bỏ ăn 2 bữa',
            'time' => '14:00 15/04',
            'note' => 'Bé lừ đừ, rớt dãi nhiều',
        ],
        [
            'id' => 'BK1092',
            'customer' => 'Lê Thị F',
            'pet' => 'Bông (Mèo Anh)',
            'condition' => 'Tiêu chảy',
            'time' => '16:30 15/04',
            'note' => 'Phân lỏng, có mùi tanh',
        ],
    ];

    $inventoryAlerts = [
        [
            'id' => 'VT_005',
            'name' => 'Cát vệ sinh CleanCat 10L',
            'stock' => 2,
            'lastUpdated' => '08:00 15/04',
            'note' => 'Sẽ hết trong chiều nay, NCC báo mai mới giao',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL RISK
    |--------------------------------------------------------------------------
    */
    $riskData = [
        'cancelCount' => 4,
        'lostValue' => 2500000,
    ];

    $alertGrid = [
        [
            'id' => 'BK1001',
            'customer' => 'Nguyễn Văn A',
            'service' => 'Suite Room',
            'time' => '10:00 15/04',
            'cancelTime' => '08:00 15/04',
            'deposit' => 500000,
            'warning' => 'Hủy sát giờ',
        ],
        [
            'id' => 'BK1005',
            'customer' => 'Phạm Thị B',
            'service' => 'Spa/Grooming',
            'time' => '15:30 15/04',
            'cancelTime' => '14:55 15/04',
            'deposit' => 0,
            'warning' => 'Không đặt cọc',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | OPERATIONAL REPORT
    |--------------------------------------------------------------------------
    */
    $barData = [
        ['name' => 'Lưu chuồng Suite', 'revenue' => 35000000],
        ['name' => 'Cắt tỉa trọn gói', 'revenue' => 20000000],
        ['name' => 'Tắm diệt rận', 'revenue' => 15000000],
    ];

    $barConfigs = [
        ['key' => 'revenue', 'name' => 'Doanh thu (VNĐ)', 'color' => '#3B82F6'],
    ];

    $pieData = [
        ['name' => 'Grooming & Spa', 'value' => 45000000],
        ['name' => 'Pet Hotel', 'value' => 75000000],
    ];

    $pieColors = ['#10B981', '#F59E0B'];

    $debtData = [
        [
            'id' => 'CUS001',
            'name' => 'Lê Văn C',
            'debt' => 1500000,
            'date' => '12/04/2026',
            'phone' => '0901234567',
        ],
        [
            'id' => 'CUS002',
            'name' => 'Nguyễn Thị D',
            'debt' => 850000,
            'date' => '14/04/2026',
            'phone' => '0911222333',
        ],
    ];

    function managerMoney($amount) {
        return number_format($amount, 0, ',', '.') . ' đ';
    }
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
        title="Chi nhánh Quận 1"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
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
                @if (count($healthAlerts) === 0)
                    <div class="safe-state-card" data-health-warning-card data-severity="green">
                        <div class="health-warning-summary" data-health-warning-summary data-severity="green">
                            <span><strong data-health-warning-count>0</strong> pet c&#7847;n theo d&#245;i</span>
                        </div>
                        ✅ <span>0 Cảnh báo Y tế - Tất cả các bé đều đang khỏe mạnh và ăn uống tốt.</span>
                    </div>
                @else
                    <div class="manager-alert-card" data-health-warning-card data-severity="yellow">
                        <h3 class="manager-alert-title manager-alert-title--red" data-health-warning-text>
                            🚨 Báo động Y tế: {{ count($healthAlerts) }} bé có dấu hiệu bất thường!
                        </h3>

                        <div class="health-warning-summary" data-health-warning-summary data-severity="yellow">
                            <span><strong data-health-warning-count>{{ count($healthAlerts) }}</strong> pet c&#7847;n theo d&#245;i</span>
                        </div>

                        <div class="manager-grid-table">
                            <div class="manager-grid-header manager-health-header">
                                <div>Mã Booking</div>
                                <div>Khách hàng</div>
                                <div>Tên bé</div>
                                <div>Tình trạng</div>
                                <div>Phát hiện lúc</div>
                                <div>Ghi chú (Staff)</div>
                                <div class="text-center">Thao tác</div>
                            </div>

                            <div class="manager-grid-body">
                                @foreach ($healthAlerts as $item)
                                    <div class="manager-grid-row manager-health-row">
                                        <div class="cell-bold">{{ $item['id'] }}</div>
                                        <div>{{ $item['customer'] }}</div>
                                        <div class="cell-bold">{{ $item['pet'] }}</div>
                                        <div class="cell-red">{{ $item['condition'] }}</div>
                                        <div>{{ $item['time'] }}</div>
                                        <div class="cell-note">{{ $item['note'] }}</div>
                                        <div class="text-center">
                                            <button type="button" class="btn-red">Ghi log xử lý</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Inventory alerts --}}
                @if (count($inventoryAlerts) === 0)
                    <div class="safe-state-card" data-inventory-warning-card data-severity="green">
                        <div class="inventory-warning-summary" data-inventory-warning-summary data-severity="green">
                            <span><strong data-inventory-out-of-stock-count>0</strong> v&#7853;t t&#432; h&#7871;t h&#224;ng</span>
                            <span><strong data-inventory-low-stock-count>0</strong> v&#7853;t t&#432; s&#7855;p h&#7871;t</span>
                        </div>
                        📦 <span>0 Cảnh báo Kho - Vật tư tiêu hao đang ở mức an toàn.</span>
                    </div>
                @else
                    <div class="manager-alert-card" data-inventory-warning-card data-severity="yellow">
                        <h3 class="manager-alert-title manager-alert-title--orange" data-inventory-warning-text>
                            ⚠️ Cảnh báo Tồn kho: {{ count($inventoryAlerts) }} vật tư sắp cạn!
                        </h3>

                        <div class="inventory-warning-summary" data-inventory-warning-summary data-severity="yellow">
                            <span><strong data-inventory-out-of-stock-count>0</strong> v&#7853;t t&#432; h&#7871;t h&#224;ng</span>
                            <span><strong data-inventory-low-stock-count>{{ count($inventoryAlerts) }}</strong> v&#7853;t t&#432; s&#7855;p h&#7871;t</span>
                        </div>

                        <div class="manager-grid-table">
                            <div class="manager-grid-header manager-inventory-header">
                                <div>Mã VT</div>
                                <div>Tên vật tư</div>
                                <div>Tồn kho</div>
                                <div>Cập nhật cuối</div>
                                <div>Ghi chú (Kho)</div>
                                <div class="text-center">Thao tác</div>
                            </div>

                            <div class="manager-grid-body">
                                @foreach ($inventoryAlerts as $item)
                                    <div class="manager-grid-row manager-inventory-row">
                                        <div class="cell-bold">{{ $item['id'] }}</div>
                                        <div>{{ $item['name'] }}</div>
                                        <div class="cell-warning">{{ $item['stock'] }}</div>
                                        <div>{{ $item['lastUpdated'] }}</div>
                                        <div class="cell-note">{{ $item['note'] }}</div>
                                        <div class="text-center">
                                            <button type="button" class="btn-orange">Ghi log xử lý</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

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
                <span>⚠️</span> Radar Cảnh Báo & Rủi Ro Tài Chính
            </h2>

            <div class="risk-summary-grid">
                <div class="risk-summary-card" data-financial-risk-count-card>
                    <p>Tổng số ca Hủy (Trong kỳ)</p>
                    <strong><span data-financial-risk-cancelled-count>{{ $riskData['cancelCount'] }}</span> <span>ca</span></strong>
                </div>

                <div class="risk-summary-card" data-financial-risk-amount-card>
                    <p>Tổng Giá Trị Thất Thoát</p>
                    <strong class="text-red" data-financial-risk-lost-amount>{{ managerMoney($riskData['lostValue']) }}</strong>
                    <small>* Dựa trên grand_total của hóa đơn bị hủy</small>
                </div>
            </div>

            <div class="financial-risk-warning" data-financial-risk-warning-text data-severity="yellow">
                C&#7843;nh b&#225;o nh&#7865;: c&#243; &#273;&#417;n h&#7911;y/ho&#224;n.
            </div>

            @if (count($alertGrid) === 0)
                <div class="empty-message">Không có cảnh báo rủi ro nào.</div>
            @else
                <div class="manager-grid-table">
                    <div class="manager-grid-header manager-risk-header">
                        <div>Mã Booking</div>
                        <div>Khách hàng</div>
                        <div>Dịch vụ</div>
                        <div>Giờ hẹn</div>
                        <div>Thời điểm hủy</div>
                        <div class="text-right">Tiền cọc</div>
                        <div>Nhãn Cảnh báo</div>
                        <div class="text-center">Thao tác</div>
                    </div>

                    <div class="manager-grid-body">
                        @foreach ($alertGrid as $item)
                            <div class="manager-grid-row manager-risk-row">
                                <div class="cell-bold">{{ $item['id'] }}</div>
                                <div>{{ $item['customer'] }}</div>
                                <div>{{ $item['service'] }}</div>
                                <div>{{ $item['time'] }}</div>
                                <div class="cell-red">{{ $item['cancelTime'] }}</div>
                                <div class="text-right">{{ managerMoney($item['deposit']) }}</div>
                                <div>
                                    <span class="{{ $item['deposit'] == 0 ? 'risk-badge risk-badge--danger' : 'risk-badge risk-badge--warning' }}">
                                        {{ $item['warning'] }}
                                    </span>
                                </div>
                                <div class="text-center">
                                    <button type="button" class="btn-blue">Ghi log</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>


        {{-- OPERATIONAL REPORT --}}
        <section class="manager-operational-report">
            <div class="operational-left">

                <div class="manager-card">
                    <div class="manager-card-header">
                        <h3>Top 5 Dịch Vụ Đem Lại Doanh Thu Cao Nhất</h3>
                    </div>

                    <div class="manager-chart-wrapper">
                        <x-chart.bar
                            id="managerTopRevenueServicesChart"
                            :data="$barData"
                            xAxisKey="name"
                            :barConfigs="$barConfigs"
                            yAxisFormatter="currency"
                            height="320px"
                        />
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
                        <x-chart.pie
                            id="managerRevenueStructureChart"
                            :data="$pieData"
                            nameKey="name"
                            dataKey="value"
                            :colors="$pieColors"
                            tooltipFormatter="currency"
                            height="320px"
                        />
                    </div>

                    <div class="pie-summary" data-revenue-structure-summary>
                        Đang xem báo cáo theo: <strong>{{ $period }}</strong>.
                        Sự chênh lệch tỷ trọng sẽ giúp bạn quyết định điều hướng Marketing kịp thời.
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
