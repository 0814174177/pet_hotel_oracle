@extends('layouts.ceo')

@section('title', 'Quản lý khuyến mãi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/promotion-management.css') }}">
@endpush

@section('content')
@php
    $promotionFormType = old('discount_type', 'PERCENT');
@endphp

<div
    id="ceoPromotionPage"
    class="ceo-promotion-page"
    data-promotion-has-errors="{{ $errors->any() ? '1' : '0' }}"
    data-end-url-template="{{ route('ceo.promotions.end', ['coupon' => '__COUPON_ID__']) }}"
>
    <script type="application/json" id="ceoPromotionCouponsData">@json($coupons ?? [])</script>

    <header class="ceo-promotion-header">
        <div>
            <h1>Quản lý khuyến mãi</h1>
            <p>Quản lý mã coupon, điều kiện sử dụng và trạng thái hiệu lực trên toàn hệ thống.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="ceo-promotion-flash ceo-promotion-flash--success">{{ session('status') }}</div>
    @endif

    <section class="ceo-promotion-stats-grid" aria-label="Tổng quan khuyến mãi">
        <article class="ceo-promotion-stat-card">
            <div class="ceo-promotion-stat-label">Tổng mã coupon</div>
            <div class="ceo-promotion-stat-value" data-stat-total>0</div>
            <div class="ceo-promotion-stat-sub">Trong hệ thống</div>
        </article>

        <article class="ceo-promotion-stat-card">
            <div class="ceo-promotion-stat-label">Đang hoạt động</div>
            <div class="ceo-promotion-stat-value ceo-promotion-stat-value--active" data-stat-active>0</div>
            <div class="ceo-promotion-stat-sub">
                <span class="ceo-promotion-stat-dot ceo-promotion-stat-dot--active"></span>
                Mã còn hiệu lực
            </div>
        </article>

        <article class="ceo-promotion-stat-card">
            <div class="ceo-promotion-stat-label">Tổng lượt dùng</div>
            <div class="ceo-promotion-stat-value" data-stat-used>0</div>
            <div class="ceo-promotion-stat-sub">Tổng lượt đã sử dụng</div>
        </article>

        <article class="ceo-promotion-stat-card">
            <div class="ceo-promotion-stat-label">Sắp hết hạn</div>
            <div class="ceo-promotion-stat-value ceo-promotion-stat-value--warning" data-stat-expiring>0</div>
            <div class="ceo-promotion-stat-sub">Trong 30 ngày tới</div>
        </article>
    </section>

    <section class="ceo-promotion-filter-card" aria-label="Bộ lọc khuyến mãi">
        <div class="ceo-promotion-filter-row">
            <label class="ceo-promotion-search-box">
                <span class="ceo-promotion-search-icon">🔍</span>
                <input type="text" placeholder="Tìm mã coupon, ghi chú..." data-search-input>
            </label>

            <select class="ceo-promotion-filter-select" data-filter-type aria-label="Lọc loại khuyến mãi">
                <option value="">Loại</option>
                <option value="PERCENT">Phần trăm (%)</option>
                <option value="FIXED">Cố định (VNĐ)</option>
            </select>

            <select class="ceo-promotion-filter-select" data-filter-status aria-label="Lọc trạng thái">
                <option value="">Trạng thái</option>
                <option value="1">Hoạt động</option>
                <option value="0">Hết hạn</option>
            </select>

            <button type="button" class="ceo-promotion-btn-add" data-add-promotion>
                <span>＋</span>
                Thêm mã khuyến mãi
            </button>
        </div>
    </section>

    <section class="ceo-promotion-table-card">
        <div class="ceo-promotion-table-header">
            <div>
                <span class="ceo-promotion-table-title">Danh sách mã coupon</span>
                <span class="ceo-promotion-table-count" data-table-count>(0 mã)</span>
            </div>
        </div>

        <div class="ceo-promotion-table-wrapper">
            <table class="ceo-promotion-table">
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
                        <th class="ceo-promotion-col-active">Hoạt động</th>
                    </tr>
                </thead>
                <tbody data-coupon-table-body></tbody>
            </table>
        </div>

        <div class="ceo-promotion-empty-state ceo-promotion-hidden" data-empty-state>
            <div class="ceo-promotion-empty-icon">🏷️</div>
            <p>Không tìm thấy mã coupon nào phù hợp</p>
        </div>
    </section>

    <div class="ceo-promotion-overlay" data-modal-overlay aria-hidden="true">
        <div class="ceo-promotion-modal" role="dialog" aria-modal="true" aria-labelledby="promotionModalTitle">
            <div class="ceo-promotion-modal-topbar">
                <div>
                    <div class="ceo-promotion-modal-title" id="promotionModalTitle" data-modal-title>Thêm khuyến mãi mới</div>
                    <div class="ceo-promotion-modal-sub" data-modal-sub>Tạo mã coupon mới trong hệ thống</div>
                </div>
                <button type="button" class="ceo-promotion-btn-close" data-close-modal aria-label="Đóng">✕</button>
            </div>

            <form class="ceo-promotion-modal-body" method="POST" action="{{ route('ceo.promotions.store') }}" data-promotion-form>
                @csrf
                <input type="hidden" name="discount_type" value="{{ $promotionFormType }}" data-field-type>

                @if ($errors->any())
                    <div class="ceo-promotion-form-errors">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <section class="ceo-promotion-form-card">
                    <div class="ceo-promotion-form-section">Thông tin cơ bản</div>
                    <div class="ceo-promotion-form-grid">
                        <label class="ceo-promotion-form-group ceo-promotion-form-group--full">
                            <span class="ceo-promotion-form-label">Mã coupon <span class="ceo-promotion-required">*</span></span>
                            <input class="ceo-promotion-input" type="text" name="coupon_code" maxlength="50" placeholder="VD: SUMMER2026" value="{{ old('coupon_code') }}" data-field-code>
                            <span class="ceo-promotion-field-note">Viết hoa, không dấu, không khoảng trắng</span>
                        </label>

                        <label class="ceo-promotion-form-group ceo-promotion-form-group--full">
                            <span class="ceo-promotion-form-label">Ghi chú</span>
                            <textarea class="ceo-promotion-textarea" name="notes" placeholder="VD: Giảm 10% tối đa 100.000đ cho đơn hàng." data-field-notes>{{ old('notes') }}</textarea>
                        </label>
                    </div>
                </section>

                <section class="ceo-promotion-form-card">
                    <div class="ceo-promotion-form-section">Loại &amp; Giá trị giảm giá</div>
                    <div class="ceo-promotion-type-row" data-type-grid>
                        <button type="button" class="ceo-promotion-type-option {{ $promotionFormType === 'PERCENT' ? 'active' : '' }}" data-type-option="PERCENT">
                            <span class="ceo-promotion-type-icon">🏷️</span>
                            <span>
                                <span class="ceo-promotion-type-label">Phần trăm</span>
                                <span class="ceo-promotion-type-desc">Giảm theo phần trăm</span>
                            </span>
                        </button>

                        <button type="button" class="ceo-promotion-type-option {{ $promotionFormType === 'FIXED' ? 'active' : '' }}" data-type-option="FIXED">
                            <span class="ceo-promotion-type-icon">💵</span>
                            <span>
                                <span class="ceo-promotion-type-label">Cố định (VNĐ)</span>
                                <span class="ceo-promotion-type-desc">Giảm số tiền cố định</span>
                            </span>
                        </button>
                    </div>

                    <div class="ceo-promotion-form-grid">
                        <label class="ceo-promotion-form-group">
                            <span class="ceo-promotion-form-label">Giá trị giảm <span class="ceo-promotion-required">*</span></span>
                            <span class="ceo-promotion-addon">
                                <input type="number" min="0" step="0.01" name="discount_value" placeholder="0" value="{{ old('discount_value') }}" data-field-value>
                                <span class="ceo-promotion-addon-text" data-field-unit>%</span>
                            </span>
                            <span class="ceo-promotion-field-note" data-value-note>Nhập 1-100 cho phần trăm</span>
                        </label>

                        <label class="ceo-promotion-form-group" data-max-discount-group>
                            <span class="ceo-promotion-form-label">Giảm tối đa</span>
                            <span class="ceo-promotion-addon">
                                <input type="number" min="0" step="0.01" name="max_discount" placeholder="VD: 100000" value="{{ old('max_discount') }}" data-field-max-discount>
                                <span class="ceo-promotion-addon-text">VNĐ</span>
                            </span>
                            <span class="ceo-promotion-field-note">Để trống = không giới hạn</span>
                        </label>

                        <label class="ceo-promotion-form-group">
                            <span class="ceo-promotion-form-label">Đơn tối thiểu</span>
                            <span class="ceo-promotion-addon">
                                <input type="number" min="0" step="0.01" name="min_order_value" placeholder="VD: 200000" value="{{ old('min_order_value') }}" data-field-min-order>
                                <span class="ceo-promotion-addon-text">VNĐ</span>
                            </span>
                        </label>

                        <label class="ceo-promotion-form-group">
                            <span class="ceo-promotion-form-label">Lượt dùng tối đa</span>
                            <input class="ceo-promotion-input" type="number" min="1" name="max_uses" placeholder="VD: 100" value="{{ old('max_uses') }}" data-field-max-uses>
                            <span class="ceo-promotion-field-note">Để trống = không giới hạn</span>
                        </label>
                    </div>
                </section>

                <section class="ceo-promotion-form-card">
                    <div class="ceo-promotion-form-section">Thời gian hiệu lực</div>
                    <div class="ceo-promotion-form-grid">
                        <label class="ceo-promotion-form-group">
                            <span class="ceo-promotion-form-label">Ngày bắt đầu <span class="ceo-promotion-required">*</span></span>
                            <input class="ceo-promotion-input" type="date" name="effective_from" value="{{ old('effective_from') }}" data-field-from>
                        </label>

                        <label class="ceo-promotion-form-group">
                            <span class="ceo-promotion-form-label">Ngày hết hạn <span class="ceo-promotion-required">*</span></span>
                            <input class="ceo-promotion-input" type="date" name="expired_at" value="{{ old('expired_at') }}" data-field-expired>
                        </label>
                    </div>
                </section>

                <section class="ceo-promotion-form-card">
                    <div class="ceo-promotion-status-row">
                        <div>
                            <div class="ceo-promotion-status-title">Kích hoạt</div>
                            <div class="ceo-promotion-status-desc">Coupon có hiệu lực trong khoảng thời gian đã thiết lập</div>
                        </div>
                        <label class="ceo-promotion-toggle">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }} data-field-active>
                            <span class="ceo-promotion-slider"></span>
                        </label>
                    </div>

                    <div class="ceo-promotion-preview-box">
                        <div class="ceo-promotion-preview-label">Xem trước mã coupon</div>
                        <div class="ceo-promotion-preview-code" data-preview-code>NHAP_MA_COUPON</div>
                    </div>
                </section>

                <div class="ceo-promotion-modal-footer">
                    <button type="button" class="ceo-promotion-btn-cancel" data-close-modal>Hủy</button>
                    <button type="submit" class="ceo-promotion-btn-primary" data-submit-promotion>✓ Tạo khuyến mãi</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ceo-promotion-confirm-overlay" data-confirm-overlay aria-hidden="true">
        <div class="ceo-promotion-confirm-box" role="dialog" aria-modal="true">
            <div class="ceo-promotion-confirm-icon">⚠️</div>
            <div class="ceo-promotion-confirm-title">Kết thúc mã khuyến mãi</div>
            <div class="ceo-promotion-confirm-msg">Bạn có muốn kết thúc mã khuyến mãi này không?</div>
            <div class="ceo-promotion-confirm-btns">
                <button type="button" class="ceo-promotion-btn-confirm-cancel" data-cancel-toggle>Hủy</button>
                <button type="button" class="ceo-promotion-btn-confirm-ok" data-confirm-toggle>Kết thúc</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/ceo/promotion-management.js') }}"></script>
@endpush
