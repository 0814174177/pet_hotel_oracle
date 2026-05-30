@extends('layouts.ceo')

@section('title', 'Quản trị Chuỗi cung ứng & Đối tác')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/partner-vendor-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: VENDOR TIERING
    |--------------------------------------------------------------------------
    */
    $vendors = [
        [
            'id' => 'V001',
            'name' => 'Pet Food Solution Co.',
            'category' => 'Thức ăn & Dinh dưỡng',
            'spend' => 1250000000,
            'otd' => 98,
            'score' => 4.8,
            'tier' => 'Platinum',
        ],
        [
            'id' => 'V002',
            'name' => 'Dược phẩm Thú y Á Âu',
            'category' => 'Thuốc & Vaccine',
            'spend' => 850000000,
            'otd' => 92,
            'score' => 4.5,
            'tier' => 'Gold',
        ],
        [
            'id' => 'V003',
            'name' => 'Xưởng Nệm Happy Pet',
            'category' => 'Phụ kiện & Đồ dùng',
            'spend' => 320000000,
            'otd' => 85,
            'score' => 3.9,
            'tier' => 'Silver',
        ],
        [
            'id' => 'V004',
            'name' => 'Đại lý Cát Sài Gòn',
            'category' => 'Vật tư tiêu hao',
            'spend' => 150000000,
            'otd' => 70,
            'score' => 3.2,
            'tier' => 'Silver',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: SPEND & PAYABLES
    |--------------------------------------------------------------------------
    */
    $totalPayables = 450500000;
    $payableTrend = '+12.5%';

    $spendChartData = [
        ['month' => 'T1', 'spend' => 320],
        ['month' => 'T2', 'spend' => 350],
        ['month' => 'T3', 'spend' => 280],
        ['month' => 'T4', 'spend' => 310],
        ['month' => 'T5', 'spend' => 420],
        ['month' => 'T6', 'spend' => 450],
    ];

    $spendBarConfigs = [
        [
            'key' => 'spend',
            'name' => 'Chi tiêu nhập hàng (Triệu VNĐ)',
            'color' => '#f87171',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: VENDOR PERFORMANCE
    |--------------------------------------------------------------------------
    */
    $avgOtd = 82.5;
    $qualityScore = 4.2;
    $lowOtdVendors = 2;

    /*
    |--------------------------------------------------------------------------
    | DATA MẪU: PRICE VARIANCE ALERTS
    |--------------------------------------------------------------------------
    */
    $priceAlerts = [
        [
            'id' => 'P01',
            'itemName' => 'Cát đậu nành hữu cơ CleanCat 10L',
            'vendorName' => 'Đại lý Cát Sài Gòn',
            'oldPrice' => 110000,
            'newPrice' => 137500,
            'variance' => 25.0,
            'purchaser' => 'NV. Trần Thu M.',
        ],
        [
            'id' => 'P02',
            'itemName' => 'Sữa tắm SOS cho chó lông trắng 5L',
            'vendorName' => 'Pet Food Solution Co.',
            'oldPrice' => 250000,
            'newPrice' => 285000,
            'variance' => 14.0,
            'purchaser' => 'NV. Lê Văn K.',
        ],
        [
            'id' => 'P03',
            'itemName' => 'Khăn tắm siêu thấm hút thú cưng',
            'vendorName' => 'Xưởng dệt may An Phú',
            'oldPrice' => 35000,
            'newPrice' => 43050,
            'variance' => 23.0,
            'purchaser' => 'NV. Trần Thu M.',
        ],
    ];

    function vendorMoney($amount) {
        return number_format($amount, 0, ',', '.') . ' đ';
    }

    function vendorTierClass($tier) {
        return match ($tier) {
            'Platinum' => 'vendor-tier vendor-tier--platinum',
            'Gold' => 'vendor-tier vendor-tier--gold',
            default => 'vendor-tier vendor-tier--silver',
        };
    }

    function gaugeColorClass($value) {
        if ($value < 85) {
            return 'vendor-gauge__value vendor-gauge__value--danger';
        }

        if ($value < 95) {
            return 'vendor-gauge__value vendor-gauge__value--warning';
        }

        return 'vendor-gauge__value vendor-gauge__value--good';
    }
@endphp

<div class="partner-vendor-page">

    <x-global-control-panel
        title="Quản trị Chuỗi cung ứng & Đối tác"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
    />

    <div class="partner-vendor-grid">

        {{-- KHU VỰC 1: PHÂN HẠNG ĐỐI TÁC --}}
        <section class="partner-card partner-card--large">
            <div class="partner-card__title">
                <span>📊 Danh sách & Phân hạng Đối tác</span>
                <button type="button" class="partner-detail-btn">Xem tất cả</button>
            </div>

            <div class="partner-table-wrapper">
                <table class="partner-table">
                    <thead>
                        <tr>
                            <th>Đối tác</th>
                            <th>Hạng</th>
                            <th>Tổng chi tiêu</th>
                            <th>Hiệu suất</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($vendors as $vendor)
                            <tr>
                                <td>
                                    <div class="partner-vendor-name">{{ $vendor['name'] }}</div>
                                    <div class="partner-vendor-category">{{ $vendor['category'] }}</div>
                                </td>

                                <td>
                                    <span class="{{ vendorTierClass($vendor['tier']) }}">
                                        {{ $vendor['tier'] }}
                                    </span>
                                </td>

                                <td>
                                    <div class="partner-money">{{ vendorMoney($vendor['spend']) }}</div>
                                </td>

                                <td>
                                    <div class="partner-score">
                                        <span>★</span>
                                        {{ $vendor['score'] }}
                                        <small>({{ $vendor['otd'] }}%)</small>
                                    </div>
                                </td>

                                <td>
                                    <button type="button" class="partner-detail-btn">Chi tiết</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>


        {{-- KHU VỰC 2: HIỆU SUẤT GIAO HÀNG --}}
        <section class="partner-card">
            <div class="partner-card__title">
                <span>🚚 Hiệu suất Giao hàng (OTD)</span>
            </div>

            <div class="vendor-gauge">
                <div class="vendor-gauge__arc">
                    <div class="vendor-gauge__fill" style="width: {{ $avgOtd }}%;"></div>
                </div>

                <div class="{{ gaugeColorClass($avgOtd) }}">
                    {{ $avgOtd }}%
                </div>

                <div class="vendor-gauge__label">Tỷ lệ đúng hạn</div>
            </div>

            <div class="vendor-performance-metrics">
                <div>
                    <strong>{{ $qualityScore }}/5.0</strong>
                    <span>Chất lượng hàng hóa</span>
                </div>

                <div>
                    <strong>12 hrs</strong>
                    <span>Thời gian trễ trung bình</span>
                </div>
            </div>

            @if ($avgOtd < 90)
                <div class="vendor-performance-alert">
                    <p>
                        Phát hiện <strong>{{ $lowOtdVendors }} đối tác</strong> có tỷ lệ giao hàng đúng hạn dưới 80%,
                        gây gián đoạn vật tư vận hành.
                    </p>

                    <button type="button">Đàm phán lại</button>
                </div>
            @endif
        </section>


        {{-- KHU VỰC 3: SPEND & PAYABLES --}}
        <section class="partner-card">
            <h3 class="partner-section-title">💸 Quản lý Dòng tiền & Công nợ</h3>

            <div class="partner-payable-summary">
                <div>
                    <div class="partner-total-label">Tổng công nợ phải trả</div>
                    <div class="partner-total-amount">{{ vendorMoney($totalPayables) }}</div>
                </div>

                <div class="partner-trend-badge">
                    📈 {{ $payableTrend }} so với tháng trước
                </div>
            </div>

            <div class="partner-chart-area">
                <x-chart.bar
                    :data="$spendChartData"
                    xAxisKey="month"
                    :barConfigs="$spendBarConfigs"
                    yAxisFormatter="raw"
                    height="280px"
                />
            </div>

            <div class="partner-insight">
                <strong>💡 Phân tích rủi ro:</strong>
                Chi tiêu tháng 5 và 6 tăng đột biến 35% do nhập trước đợt hàng Thức ăn hạt & Cát vệ sinh.
                Cần chuẩn bị quỹ tiền mặt dự phòng để thanh toán cho các hóa đơn đến hạn vào tuần tới.
            </div>
        </section>


        {{-- KHU VỰC 4: PRICE VARIANCE ALERTS --}}
        <section class="partner-card partner-card--wide">
            <div class="partner-alert-header">
                <h3>🚨 Cảnh báo Biến động giá nhập</h3>
                <p>
                    Hệ thống soi chiếu giá nhập mới so với giá trung bình 3 tháng trước.
                    Dòng cảnh báo đỏ báo hiệu biên độ đội giá vượt mức 20%.
                </p>
            </div>

            <div class="partner-table-wrapper">
                <table class="partner-table">
                    <thead>
                        <tr>
                            <th>Vật tư / Sản phẩm</th>
                            <th>Giá TB (3 Tháng)</th>
                            <th>Giá Nhập Mới</th>
                            <th>Biến động (%)</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($priceAlerts as $item)
                            @php
                                $isCritical = $item['variance'] > 20;
                            @endphp

                            <tr class="{{ $isCritical ? 'partner-critical-row' : 'partner-normal-row' }}">
                                <td>
                                    <div class="partner-item-name">{{ $item['itemName'] }}</div>
                                    <div class="partner-vendor-category">NCC: {{ $item['vendorName'] }}</div>
                                    <div class="partner-purchaser">Người phụ trách: {{ $item['purchaser'] }}</div>
                                </td>

                                <td>
                                    <span class="partner-money">{{ vendorMoney($item['oldPrice']) }}</span>
                                </td>

                                <td>
                                    <span class="{{ $isCritical ? 'partner-money partner-money--danger' : 'partner-money' }}">
                                        {{ vendorMoney($item['newPrice']) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="{{ $isCritical ? 'partner-variance partner-variance--critical' : 'partner-variance partner-variance--warning' }}">
                                        +{{ $item['variance'] }}%
                                    </span>
                                </td>

                                <td>
                                    <button type="button" class="partner-investigate-btn">
                                        🔍 Điều tra
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>
@endsection
