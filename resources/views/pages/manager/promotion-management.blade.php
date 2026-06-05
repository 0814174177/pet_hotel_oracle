@extends('layouts.manager')

@section('title', 'Quản lý khuyến mãi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/promotion-management.css') }}?v={{ time() }}">
@endpush

@section('content')
<div id="managerPromotionPage" class="manager-promotion-page">
    <script type="application/json" id="managerPromotionCouponsData">
        @json($coupons ?? [])
    </script>

    <header class="manager-promotion-header">
        <div>
            <p class="manager-promotion-eyebrow">Quản trị chi nhánh</p>
            <h1>Quản lý khuyến mãi</h1>
        </div>
        <div class="manager-promotion-header-note">
            Dữ liệu coupon hiện hành trong hệ thống
        </div>
    </header>

    <section class="manager-promotion-stats-grid" aria-label="Tổng quan khuyến mãi">
        <x-kpi-card
            title="Tổng số mã"
            value="0"
            detail="Tất cả coupon"
            :value-attributes="['data-stat-total' => true]"
        />

        <x-kpi-card
            title="Đang hoạt động"
            value="0"
            detail="Mã còn hiệu lực"
            :value-attributes="['data-stat-active' => true]"
        />

        <x-kpi-card
            title="Hết hạn"
            value="0"
            detail="Đã qua hạn dùng"
            :value-attributes="['data-stat-expired' => true]"
        />

        <x-kpi-card
            title="Đã kết thúc"
            value="0"
            detail="Đã ngừng kích hoạt"
            :value-attributes="['data-stat-ended' => true]"
        />
    </section>

    <section class="manager-promotion-filter-card" aria-label="Bộ lọc khuyến mãi">
        <label class="manager-promotion-field manager-promotion-field--search">
            <span>Tìm kiếm</span>
            <input type="text" placeholder="Tìm mã coupon hoặc ghi chú" data-search-input>
        </label>

        <label class="manager-promotion-field">
            <span>Loại khuyến mãi</span>
            <select data-filter-type>
                <option value="">Tất cả loại</option>
                <option value="PERCENT">Phần trăm</option>
                <option value="FIXED">Cố định</option>
            </select>
        </label>

        <label class="manager-promotion-field">
            <span>Trạng thái</span>
            <select data-filter-status>
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động</option>
                <option value="expired">Hết hạn</option>
                <option value="ended">Đã kết thúc</option>
            </select>
        </label>

        <button type="button" class="manager-promotion-btn-refresh" data-refresh-filters>Làm mới</button>
    </section>

    <section class="manager-promotion-table-card">
        <div class="manager-promotion-table-header">
            <div>
                <h2>Danh sách mã khuyến mãi</h2>
                <span data-table-count>0 kết quả</span>
            </div>
        </div>

        <div class="manager-promotion-table-wrapper">
            <table class="manager-promotion-table">
                <thead>
                    <tr>
                        <th>Mã coupon</th>
                        <th>Loại</th>
                        <th>Giá trị</th>
                        <th>Giảm tối đa</th>
                        <th>Đơn tối thiểu</th>
                        <th>Lượt dùng</th>
                        <th>Hiệu lực</th>
                        <th>Hết hạn</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody data-coupon-table-body></tbody>
            </table>
        </div>

        <div class="manager-promotion-empty-state manager-promotion-hidden" data-empty-state>
            <p>Không tìm thấy mã khuyến mãi phù hợp.</p>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/promotion-management.js') }}?v={{ time() }}"></script>
@endpush
