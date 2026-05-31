@extends('layouts.ceo')

@section('title', 'Quản trị danh mục dịch vụ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/service-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $stats = [
        [
            'title' => "Dịch vụ 'Gánh' Doanh thu",
            'value' => 'Tắm cắt tỉa trọn gói',
            'trend' => 'Chiếm 45% tổng doanh thu Spa',
            'isPositive' => true,
            'period' => 'tháng',
        ],
        [
            'title' => 'Dịch vụ Siêu lợi nhuận',
            'value' => 'Spa phục hồi lông',
            'trend' => 'Margin đạt 70%',
            'isPositive' => true,
            'period' => 'tháng',
        ],
        [
            'title' => 'Dịch vụ không phát sinh doanh thu',
            'value' => '05 dịch vụ',
            'trend' => 'Trong 30 ngày qua',
            'isPositive' => false,
            'period' => 'tháng',
        ],
    ];

    $services = [
        [
            'id' => 'SPA-001',
            'name' => 'Tắm cắt tỉa trọn gói',
            'status' => 'active',
            'price' => 500000,
            'cogs' => 150000,
            'margin' => 70,
            'serveCount' => 1240,
            'coverage' => 8,
            'totalBranches' => 10,
        ],
        [
            'id' => 'HOT-002',
            'name' => 'Lưu chuồng VIP (Mèo)',
            'status' => 'active',
            'price' => 300000,
            'cogs' => 50000,
            'margin' => 83,
            'serveCount' => 450,
            'coverage' => 10,
            'totalBranches' => 10,
        ],
        [
            'id' => 'CLI-003',
            'name' => 'Khám sức khỏe tổng quát',
            'status' => 'paused',
            'price' => 400000,
            'cogs' => 350000,
            'margin' => 12,
            'serveCount' => 15,
            'coverage' => 2,
            'totalBranches' => 10,
        ],
    ];

    function formatServiceMoney($amount) {
        if ($amount >= 1000) {
            return ($amount / 1000) . 'k';
        }

        return $amount;
    }
@endphp

<div
    class="ceo-service-page"
    id="ceoServicePage"
    data-summary-url="{{ route('api.dashboard.ceo.services.summary') }}"
    data-highest-revenue-url="{{ route('api.dashboard.ceo.services.highest-revenue') }}"
    data-lowest-revenue-url="{{ route('api.dashboard.ceo.services.lowest-revenue') }}"
    data-catalog-url="{{ route('api.dashboard.ceo.services.index') }}"
>
    <header class="ceo-service-header">
        <h1>Quản trị Danh mục Dịch vụ</h1>
        <p>Kiểm soát hiệu suất, biên lợi nhuận và độ phủ dịch vụ trên toàn chuỗi</p>
    </header>

    <x-global-control-panel
        title="Quáº£n trá»‹ Danh má»¥c Dá»‹ch vá»¥"
        period="thÃ¡ng"
        lastUpdate="14:58 - Cáº­p nháº­t thÃ nh cÃ´ng"
    />

    <section class="ceo-service-stats">
        @foreach ($stats as $item)
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

    <section class="ceo-service-content">
        <div class="ceo-service-toolbar">
            <div class="ceo-service-search">
                <input type="text" placeholder="Tìm kiếm Mã hoặc Tên dịch vụ...">
            </div>

            <div class="ceo-service-filters">
                <select>
                    <option value="">-- Hiệu suất --</option>
                    <option value="best-seller">Bán chạy nhất</option>
                    <option value="high-margin">Biên lợi nhuận cao nhất</option>
                    <option value="low-use">Ít được sử dụng nhất</option>
                </select>

                <select>
                    <option value="">-- Toàn hệ thống --</option>
                    <option value="q1">Chi nhánh Quận 1</option>
                    <option value="q3">Chi nhánh Quận 3</option>
                    <option value="td">Chi nhánh Thủ Đức</option>
                </select>

                <button type="button" class="ceo-service-add-btn">
                    + Thêm dịch vụ mới
                </button>
            </div>
        </div>

        <div class="ceo-service-grid-wrapper">
            <table class="ceo-service-table">
                <thead>
                    <tr>
                        <th>Mã/Tên</th>
                        <th>Trạng thái</th>
                        <th>Giá bán / Vốn / Margin %</th>
                        <th class="text-center">Lượt phục vụ (30 ngày)</th>
                        <th>Độ phủ (Coverage)</th>
                        <th>Thao tác quản trị</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($services as $service)
                        @php
                            $coveragePercent = $service['totalBranches'] > 0
                                ? round(($service['coverage'] / $service['totalBranches']) * 100)
                                : 0;
                        @endphp

                        <tr>
                            <td>
                                <div class="service-name">{{ $service['name'] }}</div>
                                <div class="service-code">{{ $service['id'] }}</div>
                            </td>

                            <td>
                                @if ($service['status'] === 'active')
                                    <span class="service-status service-status--active">Hoạt động</span>
                                @else
                                    <span class="service-status service-status--paused">Đã ẩn</span>
                                @endif
                            </td>

                            <td>
                                <div class="service-price-info">
                                    <span class="service-sale-price">{{ formatServiceMoney($service['price']) }}</span>
                                    <span>/</span>
                                    <span class="service-cost-price">{{ formatServiceMoney($service['cogs']) }}</span>
                                    <span>/</span>
                                    <span class="{{ $service['margin'] > 50 ? 'service-margin-high' : 'service-margin-low' }}">
                                        {{ $service['margin'] }}%
                                    </span>
                                </div>
                            </td>

                            <td class="text-center">
                                {{ number_format($service['serveCount'], 0, ',', '.') }} lượt
                            </td>

                            <td>
                                <div class="service-coverage">
                                    <span>{{ $service['coverage'] }}/{{ $service['totalBranches'] }} chi nhánh</span>

                                    <div class="service-progress">
                                        <div style="width: {{ $coveragePercent }}%;"></div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="service-action-group">
                                    <button type="button" class="service-action-btn">Xem</button>
                                    <button type="button" class="service-action-btn">Sửa</button>
                                    <button type="button" class="service-action-btn service-action-btn--danger">
                                        Ngừng hợp tác
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="service-empty">
                                Không tìm thấy dữ liệu dịch vụ phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/ceo/service-management.js') }}?v={{ time() }}"></script>
@endpush
