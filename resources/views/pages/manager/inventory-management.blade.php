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
    data-out-of-stock-url="{{ route('api.dashboard.manager.branches.inventory.materials.out-of-stock', ['branchId' => $managerBranchId]) }}"
    data-low-stock-url="{{ route('api.dashboard.manager.branches.inventory.materials.low-stock', ['branchId' => $managerBranchId]) }}"
    data-inventory-value-by-category-url="{{ route('api.dashboard.manager.branches.inventory.materials.inventory-value-by-category', ['branchId' => $managerBranchId]) }}"
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

        <x-kpi-card
            data-kpi="margin-assumption"
            title="Margin t&#7841;m t&#237;nh"
            value="&#272;ang t&#7843;i..."
            trend="Gi&#7843; &#273;&#7883;nh hi&#7879;n t&#7841;i"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />
    </section>

    <section class="inventory-warning-panel inventory-warning-panel--safe" data-inventory-warning-panel data-warning-level="safe">
        <div class="inventory-warning-panel__label">C&#7843;nh b&#225;o t&#7891;n kho</div>
        <strong data-inventory-warning-title>&#272;ang t&#7843;i...</strong>
        <p data-inventory-warning-message>&#272;ang t&#7893;ng h&#7907;p d&#7919; li&#7879;u t&#7891;n kho chi nh&#225;nh...</p>
        <div class="inventory-out-of-stock-list" data-out-of-stock-list data-low-stock-list>
            <div class="inventory-out-of-stock-empty">&#272;ang t&#7843;i danh s&#225;ch v&#7853;t t&#432; s&#7855;p h&#7871;t...</div>
        </div>
    </section>

    {{-- MAIN CONTENT --}}
    <section class="inventory-main-card">
        <section class="inventory-category-value-section" data-inventory-value-by-category-panel>
            <div class="inventory-section-heading">
                <div>
                    <div class="inventory-section-label">Ph&#226;n t&#237;ch v&#7889;n t&#7891;n</div>
                    <h2>V&#7889;n t&#7891;n kho theo nh&#243;m v&#7853;t t&#432;</h2>
                </div>
                <span data-inventory-value-by-category-summary>&#272;ang t&#7843;i...</span>
            </div>

            <div class="inventory-category-value-list" data-inventory-value-by-category-list>
                <div class="inventory-empty">&#272;ang t&#7843;i d&#7919; li&#7879;u v&#7889;n t&#7891;n kho theo nh&#243;m...</div>
            </div>
        </section>

        {{-- TOOLBAR --}}
        <div class="inventory-toolbar">
            <div class="inventory-search-group">
                <input class="js-inventory-search-filter" type="text" placeholder="Tìm kiếm Mã hoặc Tên vật tư...">

                <select class="js-inventory-group-filter">
                    <option value="">-- Tất cả nhóm --</option>
                </select>
            </div>

            <button
                type="button"
                class="inventory-add-btn"
                disabled
                title="Chuc nang them vat tu chua ho tro"
            >
                Chưa hỗ trợ thêm vật tư
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
