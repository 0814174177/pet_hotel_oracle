@extends('layouts.manager')

@section('title', 'Quản lý Dịch vụ Chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/branch-service-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $branchName = 'Chi nhánh Quận 1';
    $managerBranchId = auth()->user()?->employee?->branch_id ?? 1;
    $serviceSearch = trim((string) request('search', ''));
    $serviceGroup = trim((string) request('service_group', ''));
    $serviceStatus = trim((string) request('status', ''));
    $serviceSearchPath = $serviceSearch !== '' ? $serviceSearch : '_';
    $serviceGroupPath = $serviceGroup !== '' ? $serviceGroup : '_';
    $serviceStatusPath = $serviceStatus !== '' ? $serviceStatus : '_';

    $stats = [
        [
            'key' => 'revenue-progress',
            'title' => 'Tiến độ Doanh thu Tháng',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'detail' => 'Đang tải cảnh báo tiến độ...',
            'isPositive' => false,
            'period' => 'tháng',
        ],
        [
            'key' => 'local-top-service',
            'title' => 'Dịch vụ Mũi nhọn (Local)',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => true,
            'period' => 'tháng',
        ],
        [
            'key' => 'upsell-rate',
            'title' => 'Tỷ lệ Upsell (Bán chéo)',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'isPositive' => true,
            'period' => 'tháng',
        ],
        [
            'key' => 'revenue-drop-alerts',
            'title' => 'Dịch vụ doanh thu giảm mạnh',
            'value' => 'Đang tải...',
            'trend' => 'Chưa có dữ liệu',
            'detail' => 'Đang tải danh sách cảnh báo...',
            'isPositive' => false,
            'period' => 'kỳ',
        ],
    ];

@endphp

<div
    class="branch-service-page"
    id="managerBranchServicePage"
    data-overview-url="{{ route('api.dashboard.manager.branches.services.management.index', ['branchId' => $managerBranchId]) }}"
    data-kpi-url="{{ route('api.dashboard.manager.branches.services.management.kpi', ['branchId' => $managerBranchId]) }}"
    data-service-revenue-progress-url="{{ route('api.dashboard.manager.branches.services.revenue-progress', ['branchId' => $managerBranchId]) }}"
    data-service-revenue-drop-alerts-url="{{ route('api.dashboard.manager.branches.services.revenue-drop-alerts', ['branchId' => $managerBranchId]) }}"
    data-service-upsell-rate-url="{{ route('api.dashboard.manager.branches.services.upsell-rate', ['branchId' => $managerBranchId]) }}"
    data-services-url="{{ route('api.dashboard.manager.branches.services.filtered-list', [
        'branchId' => $managerBranchId,
        'search' => $serviceSearchPath,
        'serviceGroup' => $serviceGroupPath,
        'status' => $serviceStatusPath,
    ]) }}"
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
                data-kpi="{{ $item['key'] }}"
                :title="$item['title']"
                :value="$item['value']"
                :trend="$item['trend']"
                :detail="$item['detail'] ?? null"
                :isPositive="$item['isPositive']"
                :period="$item['period']"
                :icon="null"
            />
        @endforeach
    </section>

    <section class="branch-service-revenue-alert" data-service-revenue-progress-alert>
        <div class="alert-box-wrapper">
            <div class="alert-box alert-box--warning">
                <h4 class="alert-box__header">
                    <span class="alert-box__icon" aria-hidden="true">!</span>
                    <span data-alert-title>Đang tải cảnh báo doanh thu dịch vụ</span>
                </h4>

                <p class="alert-box__message" data-alert-message>Đang tổng hợp tiến độ so với chỉ tiêu...</p>
            </div>
        </div>
    </section>

    <section class="branch-service-main">
        {{-- TOOLBAR --}}
        <form method="GET" action="{{ route('manager.service') }}" class="branch-service-toolbar">
            <div class="branch-service-search">
                <input
                    type="text"
                    name="search"
                    value="{{ $serviceSearch }}"
                    placeholder="Tìm kiếm nhanh Mã hoặc Tên dịch vụ..."
                >
            </div>

            <div class="branch-service-filters">
                <select name="service_group">
                    <option value="">-- Tất cả nhóm --</option>
                    <option value="Tắm thú cưng" @selected($serviceGroup === 'Tắm thú cưng')>Tắm thú cưng</option>
                    <option value="Chăm sóc lông" @selected($serviceGroup === 'Chăm sóc lông')>Chăm sóc lông</option>
                    <option value="Kiểm tra sức khỏe" @selected($serviceGroup === 'Kiểm tra sức khỏe')>Kiểm tra sức khỏe</option>
                    <option value="Dịch vụ bổ sung" @selected($serviceGroup === 'Dịch vụ bổ sung')>Dịch vụ bổ sung</option>
                </select>

                <select name="status">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active" @selected($serviceStatus === 'active')>Đang mở (Active)</option>
                    <option value="inactive" @selected($serviceStatus === 'inactive')>Đã ẩn khỏi Website</option>
                    <option value="paused" @selected($serviceStatus === 'paused')>Đang khóa tạm thời (Paused)</option>
                </select>

                <button type="submit" class="branch-service-sync-btn">
                    Lọc dịch vụ
                </button>
            </div>
        </form>

        {{-- GRID --}}
        <div class="branch-service-grid-wrapper">
            <div class="branch-service-grid-header">
                <div>Mã / Tên dịch vụ / Chỉ số doanh thu</div>
                <div class="text-center">Website (read-only)</div>
                <div class="text-center">Khóa (read-only)</div>
                <div>Giá tham chiếu</div>
            </div>

            <div class="branch-service-grid-body">
                <div class="branch-service-empty">
                    <span>Đang tải dữ liệu dịch vụ...</span>
                </div>
            </div>
        </div>
    </section>

</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/branch-service-management.js') }}?v={{ time() }}"></script>
@endpush
