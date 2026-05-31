@extends('layouts.manager')

@section('title', 'Quản lý Dịch vụ Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-service-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $branchName = 'Chi nhánh Quận 1';
    $managerBranchId = auth()->user()?->employee?->branch_id ?? 1;

    $stats = [
        [
            'title' => 'Tiến độ Doanh thu Tháng',
            'value' => '75%',
            'trend' => 'So với chỉ tiêu (Target) được giao',
            'isPositive' => false,
            'period' => 'tháng',
        ],
        [
            'title' => 'Dịch vụ Mũi nhọn (Local)',
            'value' => 'Tắm cắt tỉa (Mèo)',
            'trend' => 'Đang mang lại doanh thu cao nhất',
            'isPositive' => true,
            'period' => 'tháng',
        ],
        [
            'title' => 'Tỷ lệ Upsell (Bán chéo)',
            'value' => '15%',
            'trend' => 'Khách mua thêm dịch vụ phụ',
            'isPositive' => true,
            'period' => 'tháng',
        ],
    ];

    $services = [
        [
            'id' => 'SPA-001',
            'name' => 'Tắm cắt tỉa trọn gói',
            'group' => 'Spa',
            'isGlobalActive' => true,
            'localStatus' => 'active',
            'isPaused' => false,
            'basePrice' => 500000,
            'localPrice' => 550000,
            'serveCount' => 145,
        ],
        [
            'id' => 'HOT-002',
            'name' => 'Lưu chuồng VIP (Mèo)',
            'group' => 'Hotel',
            'isGlobalActive' => true,
            'localStatus' => 'active',
            'isPaused' => true,
            'basePrice' => 300000,
            'localPrice' => 300000,
            'serveCount' => 88,
        ],
        [
            'id' => 'CLI-003',
            'name' => 'Khám sức khỏe tổng quát',
            'group' => 'Clinic',
            'isGlobalActive' => false,
            'localStatus' => 'inactive',
            'isPaused' => false,
            'basePrice' => 400000,
            'localPrice' => 400000,
            'serveCount' => 12,
        ],
    ];

    function serviceMoney($amount) {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
@endphp

<div
    class="branch-service-page"
    id="managerBranchServicePage"
    data-overview-url="{{ route('api.dashboard.manager.branches.services.management.index', ['branchId' => $managerBranchId]) }}"
    data-kpi-url="{{ route('api.dashboard.manager.branches.services.management.kpi', ['branchId' => $managerBranchId]) }}"
    data-services-url="{{ route('api.dashboard.manager.branches.services.index', ['branchId' => $managerBranchId]) }}"
>

    <x-global-control-panel
        :title="$branchName"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
    />

    {{-- KPI STATS --}}
    <section class="branch-service-stats">
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

    <section class="branch-service-main">
        {{-- TOOLBAR --}}
        <div class="branch-service-toolbar">
            <div class="branch-service-search">
                <input type="text" placeholder="Tìm kiếm nhanh Mã hoặc Tên dịch vụ...">
            </div>

            <div class="branch-service-filters">
                <select>
                    <option value="">-- Tất cả nhóm --</option>
                    <option value="spa">Spa & Grooming</option>
                    <option value="hotel">Pet Hotel</option>
                    <option value="clinic">Clinic (Y tế)</option>
                </select>

                <select>
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active">Đang mở (Active)</option>
                    <option value="inactive">Đã ẩn khỏi Website</option>
                    <option value="paused">Đang khóa tạm thời (Paused)</option>
                </select>

                <button type="button" class="branch-service-sync-btn">
                    🔄 Đồng bộ dữ liệu
                </button>
            </div>
        </div>

        {{-- GRID --}}
        <div class="branch-service-grid-wrapper">
            <div class="branch-service-grid-header">
                <div>Mã / Tên dịch vụ</div>
                <div class="text-center">Hiển thị Website</div>
                <div class="text-center">Khóa khẩn cấp</div>
                <div>Tùy chỉnh Giá (Override)</div>
            </div>

            <div class="branch-service-grid-body">
                @forelse ($services as $service)
                    @php
                        $isActive = $service['localStatus'] === 'active';
                        $isPaused = $service['isPaused'];
                        $isGlobalActive = $service['isGlobalActive'];
                        $switchChecked = $isGlobalActive && $isActive && !$isPaused;
                    @endphp

                    <div class="{{ !$isGlobalActive ? 'branch-service-grid-row branch-service-grid-row--disabled' : 'branch-service-grid-row' }}">
                        <div class="branch-service-name-cell">
                            <span class="branch-service-name">{{ $service['name'] }}</span>
                            <span class="branch-service-code">{{ $service['id'] }}</span>

                            <span class="branch-service-group">
                                Nhóm: {{ $service['group'] }} • {{ $service['serveCount'] }} lượt phục vụ
                            </span>

                            @if (!$isGlobalActive)
                                <span class="ceo-lock-badge">CEO KHÓA</span>
                            @endif
                        </div>

                        <div class="text-center">
                            <label class="branch-service-switch">
                                <input
                                    type="checkbox"
                                    {{ $switchChecked ? 'checked' : '' }}
                                    {{ !$isGlobalActive ? 'disabled' : '' }}
                                >
                                <span></span>
                            </label>
                        </div>

                        <div class="text-center">
                            <button
                                type="button"
                                class="{{ $isPaused ? 'branch-service-pause-btn branch-service-pause-btn--active' : 'branch-service-pause-btn' }}"
                                {{ !$isGlobalActive || !$isActive ? 'disabled' : '' }}
                            >
                                {{ $isPaused ? 'Đang khóa ⏸️' : 'Khóa tạm ⏸️' }}
                            </button>
                        </div>

                        <div class="branch-service-price-group">
                            <span class="branch-service-base-price">
                                Gốc: {{ serviceMoney($service['basePrice']) }}
                            </span>

                            <div class="branch-service-local-price">
                                {{ serviceMoney($service['localPrice'] ?: $service['basePrice']) }}
                                <span class="branch-service-edit-icon">✎</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="branch-service-empty">
                        <div class="branch-service-empty-icon">📦</div>
                        <span>Kho dữ liệu dịch vụ trống. Yêu cầu đồng bộ từ CEO.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/branch-service-management.js') }}?v={{ time() }}"></script>
@endpush
