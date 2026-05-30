@extends('layouts.manager')

@section('title', 'Dashboard Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-dashboard.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $period = 'tháng';

    /*
    |--------------------------------------------------------------------------
    | KPI DATA
    |--------------------------------------------------------------------------
    */
    $kpiData = [
        [
            'title' => 'Lịch Check-in',
            'value' => '120 bé',
            'trend' => '20%',
            'isPositive' => true,
            'period' => 'hôm qua',
        ],
        [
            'title' => 'Lịch Check-out',
            'value' => '115 bé',
            'trend' => '5%',
            'isPositive' => true,
            'period' => 'hôm qua',
        ],
        [
            'title' => 'Lịch Spa/Grooming',
            'value' => '200 ca',
            'trend' => '15%',
            'isPositive' => false,
            'period' => 'hôm qua',
        ],
        [
            'title' => 'Phòng trống (Walk-in)',
            'value' => '5 chuồng',
            'trend' => '3 chuồng',
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

<div class="manager-branch-dashboard">

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
                    <div class="safe-state-card">
                        ✅ <span>0 Cảnh báo Y tế - Tất cả các bé đều đang khỏe mạnh và ăn uống tốt.</span>
                    </div>
                @else
                    <div class="manager-alert-card">
                        <h3 class="manager-alert-title manager-alert-title--red">
                            🚨 Báo động Y tế: {{ count($healthAlerts) }} bé có dấu hiệu bất thường!
                        </h3>

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
                    <div class="safe-state-card">
                        📦 <span>0 Cảnh báo Kho - Vật tư tiêu hao đang ở mức an toàn.</span>
                    </div>
                @else
                    <div class="manager-alert-card">
                        <h3 class="manager-alert-title manager-alert-title--orange">
                            ⚠️ Cảnh báo Tồn kho: {{ count($inventoryAlerts) }} vật tư sắp cạn!
                        </h3>

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

            </div>
        </section>


        {{-- FINANCIAL RISK REPORT --}}
        <section class="manager-risk-section">
            <h2 class="manager-section-title">
                <span>⚠️</span> Radar Cảnh Báo & Rủi Ro Tài Chính
            </h2>

            <div class="risk-summary-grid">
                <div class="risk-summary-card">
                    <p>Tổng số ca Hủy (Trong kỳ)</p>
                    <strong>{{ $riskData['cancelCount'] }} <span>ca</span></strong>
                </div>

                <div class="risk-summary-card">
                    <p>Tổng Giá Trị Thất Thoát</p>
                    <strong class="text-red">{{ managerMoney($riskData['lostValue']) }}</strong>
                    <small>* Dựa trên grand_total của hóa đơn bị hủy</small>
                </div>
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
                            :data="$barData"
                            xAxisKey="name"
                            :barConfigs="$barConfigs"
                            yAxisFormatter="raw"
                            height="320px"
                        />
                    </div>
                </div>

                <div class="manager-card">
                    <div class="manager-card-header">
                        <h3>Sổ Ghi Công Nợ (Hóa đơn chưa thanh toán đủ)</h3>
                    </div>

                    @if (count($debtData) === 0)
                        <div class="empty-message">Không có công nợ nào cần thu trong kỳ này.</div>
                    @else
                        <div class="manager-grid-table">
                            <div class="manager-grid-header manager-debt-header">
                                <div>Mã KH</div>
                                <div>Khách hàng</div>
                                <div class="text-right">Số tiền còn nợ</div>
                                <div>Ngày phát sinh</div>
                                <div>SĐT</div>
                            </div>

                            <div class="manager-grid-body">
                                @foreach ($debtData as $item)
                                    <div class="manager-grid-row manager-debt-row">
                                        <div class="cell-medium">{{ $item['id'] }}</div>
                                        <div>{{ $item['name'] }}</div>
                                        <div class="text-right cell-red cell-bold">{{ managerMoney($item['debt']) }}</div>
                                        <div>{{ $item['date'] }}</div>
                                        <div>{{ $item['phone'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            <div class="operational-right">
                <div class="manager-card manager-card--full">
                    <div class="manager-card-header">
                        <h3>Cơ cấu Doanh thu</h3>
                    </div>

                    <div class="manager-pie-wrapper">
                        <x-chart.pie
                            :data="$pieData"
                            nameKey="name"
                            dataKey="value"
                            :colors="$pieColors"
                            height="320px"
                        />
                    </div>

                    <div class="pie-summary">
                        Đang xem báo cáo theo: <strong>{{ $period }}</strong>.
                        Sự chênh lệch tỷ trọng sẽ giúp bạn quyết định điều hướng Marketing kịp thời.
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

@endsection