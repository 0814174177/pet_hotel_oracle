@extends('layouts.manager')

@section('title', 'Khuyến mãi')

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
            <p class="manager-promotion-eyebrow">Khuyến mãi</p>
            <h1>Quản lý khuyến mãi</h1>
            <p>Xem và theo dõi trạng thái các mã khuyến mãi trong hệ thống.</p>
        </div>
    </header>

    <section class="manager-promotion-stats-grid" aria-label="Tổng quan khuyến mãi">
        <article class="manager-promotion-stat-card">
            <span class="manager-promotion-stat-icon manager-promotion-stat-icon--total">#</span>
            <div>
                <div class="manager-promotion-stat-label">Tổng số mã</div>
                <div class="manager-promotion-stat-value" data-stat-total>0</div>
                <div class="manager-promotion-stat-sub">Tất cả</div>
            </div>
        </article>

        <article class="manager-promotion-stat-card">
            <span class="manager-promotion-stat-icon manager-promotion-stat-icon--active">✓</span>
            <div>
                <div class="manager-promotion-stat-label">Đang hoạt động</div>
                <div class="manager-promotion-stat-value" data-stat-active>0</div>
                <div class="manager-promotion-stat-sub">Mã còn hiệu lực</div>
            </div>
        </article>

        <article class="manager-promotion-stat-card">
            <span class="manager-promotion-stat-icon manager-promotion-stat-icon--expired">!</span>
            <div>
                <div class="manager-promotion-stat-label">Hết hạn</div>
                <div class="manager-promotion-stat-value" data-stat-expired>0</div>
                <div class="manager-promotion-stat-sub">Đã qua hạn dùng</div>
            </div>
        </article>

        <article class="manager-promotion-stat-card">
            <span class="manager-promotion-stat-icon manager-promotion-stat-icon--ended">×</span>
            <div>
                <div class="manager-promotion-stat-label">Đã kết thúc</div>
                <div class="manager-promotion-stat-value" data-stat-ended>0</div>
                <div class="manager-promotion-stat-sub">Đã ngưng kích hoạt</div>
            </div>
        </article>
    </section>

    <section class="manager-promotion-filter-card" aria-label="Bộ lọc khuyến mãi">
        <label class="manager-promotion-search-box">
            <span class="manager-promotion-search-icon">⌕</span>
            <input type="text" placeholder="Tìm mã coupon, ghi chú..." data-search-input>
        </label>

        <label class="manager-promotion-filter-group">
            <span>Loại khuyến mãi</span>
            <select data-filter-type>
                <option value="">Tất cả loại</option>
                <option value="PERCENT">Phần trăm</option>
                <option value="FIXED">Cố định</option>
            </select>
        </label>

        <label class="manager-promotion-filter-group">
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
            <div class="manager-promotion-empty-icon">#</div>
            <p>Không tìm thấy mã khuyến mãi nào phù hợp.</p>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/promotion-management.js') }}?v={{ time() }}"></script>
@endpush
