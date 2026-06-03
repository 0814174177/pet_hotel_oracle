@extends('layouts.manager')
@section('title', 'Quản trị Danh mục Vật tư')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/inventory-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $period = 'tháng';
    $managerBranchId = auth()->user()?->employee?->branch_id;

    abort_if($managerBranchId === null, 403, 'Manager branch is required.');
@endphp

<div
    class="inventory-page"
    id="managerInventoryPage"
    data-kpi-url="{{ route('api.dashboard.manager.branches.inventory.materials.kpi', ['branchId' => $managerBranchId]) }}"
    data-materials-url="{{ route('api.dashboard.manager.branches.inventory.materials.list', ['branchId' => $managerBranchId]) }}"
>

    <x-global-control-panel
        title="Quản trị Danh mục Vật tư"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
    />

    {{-- KPI SECTION --}}
    <section class="inventory-kpi-row">
        <x-kpi-card
            data-kpi="total-materials"
            title="Tổng vật tư"
            value="Đang tải..."
            trend="Chưa có dữ liệu"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            data-kpi="out-of-stock-materials"
            title="Hết hàng"
            value="Đang tải..."
            trend="Chưa có dữ liệu"
            :isPositive="false"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            data-kpi="low-stock-materials"
            title="Sắp hết"
            value="Đang tải..."
            trend="Chưa có dữ liệu"
            :isPositive="false"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            data-kpi="inventory-capital-value"
            title="Vốn tồn kho"
            value="Đang tải..."
            trend="Chưa có dữ liệu"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />
    </section>

    {{-- MAIN CONTENT --}}
    <section class="inventory-main-card">

        {{-- TOOLBAR --}}
        <div class="inventory-toolbar">
            <div class="inventory-search-group">
                <input class="js-inventory-search-filter" type="text" placeholder="Tìm kiếm Mã hoặc Tên vật tư...">

                <select class="js-inventory-group-filter">
                    <option value="">-- Tất cả nhóm --</option>
                </select>
            </div>

            <button type="button" class="inventory-add-btn">
                + Thêm vật tư mới
            </button>
        </div>

        {{-- TABLE --}}
        <div class="inventory-table-wrapper">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Mã/Tên</th>
                        <th>Trạng thái</th>
                        <th>Tồn kho / Ngưỡng</th>
                        <th>Đơn vị</th>
                        <th>Cập nhật</th>
                        <th class="text-right">Thao tác quản trị</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="6" class="inventory-empty">
                            Đang tải dữ liệu vật tư...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </section>

</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/inventory-management.js') }}?v={{ time() }}"></script>
@endpush
