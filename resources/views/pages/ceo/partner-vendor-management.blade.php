@extends('layouts.ceo')

@section('title', 'Quản trị Chuỗi cung ứng & Đối tác')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/client/css/ceo/partner-vendor-management.css') }}?v={{ time() }}">
@endpush

@section('content')
<div
  class="partner-vendor-page"
  id="ceoVendorPage"
  data-vendors-url="{{ route('api.dashboard.ceo.vendors') }}"
  data-otd-summary-url="{{ route('api.dashboard.ceo.vendors.otd-summary') }}"
  data-payables-url="{{ route('api.dashboard.ceo.vendors.payables') }}"
  data-price-variance-url="{{ route('api.dashboard.ceo.vendors.price-variance') }}"
>
  <x-global-control-panel title="Quản trị Chuỗi cung ứng & Đối tác" />

  <div class="partner-vendor-grid">
    <section class="partner-card partner-card--large">
      <div class="partner-card__title">
        <span>Danh sách & Phân hạng Đối tác</span>
      </div>

      <div class="partner-table-wrapper">
        <table class="partner-table">
          <thead>
            <tr>
              <th>Đối tác</th>
              <th>Hạng</th>
              <th>Tổng chi tiêu</th>
              <th>Hiệu suất</th>
            </tr>
          </thead>

          <tbody data-vendors-table-body>
            <tr>
              <td class="partner-empty-row" colspan="4">Đang tải dữ liệu đối tác...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="partner-card">
      <div class="partner-card__title">
        <span>Hiệu suất Giao hàng (OTD)</span>
      </div>

      <div class="vendor-gauge">
        <div class="vendor-gauge__arc">
          <div class="vendor-gauge__fill" style="width: 0%;" data-otd-fill></div>
        </div>

        <div class="vendor-gauge__value vendor-gauge__value--good" data-otd-rate>
          --
        </div>

        <div class="vendor-gauge__label">Tỷ lệ đúng hạn</div>
      </div>

      <div class="vendor-performance-metrics">
        <div>
          <strong><span data-quality-score>--</span>/5.0</strong>
          <span>Chất lượng hàng hóa</span>
        </div>

        <div>
          <strong><span data-delay-hours>--</span> hrs</strong>
          <span>Thời gian trễ trung bình</span>
        </div>
      </div>

      <div class="vendor-performance-alert" data-otd-alert hidden>
        <p>
          Phát hiện <strong data-low-otd-vendor-count>0 đối tác</strong> có tỷ lệ giao hàng đúng hạn dưới ngưỡng,
          <span data-otd-alert-message></span>
        </p>
      </div>
    </section>

    <section class="partner-card">
      <h3 class="partner-section-title">Quản lý Dòng tiền & Công nợ</h3>

      <div class="partner-payable-summary">
        <div>
          <div class="partner-total-label">Tổng công nợ phải trả</div>
          <div class="partner-total-amount" data-payables-total>Đang tải...</div>
        </div>

        <div class="partner-trend-badge" data-payables-trend>
          Đang tải...
        </div>
      </div>

      <div class="partner-chart-area">
        <div class="chart-component chart-component--bar" style="--chart-height: 280px;">
          <canvas id="vendorPayablesChart" class="chart-component__canvas"></canvas>
        </div>
      </div>

      <div class="partner-insight" data-payables-insight>
        <strong>Phân tích rủi ro:</strong> Đang tải phân tích dòng tiền.
      </div>
    </section>

    <section class="partner-card partner-card--wide">
      <div class="partner-alert-header">
        <h3>Cảnh báo Biến động giá nhập</h3>
        <p>
          Hệ thống soi chiếu giá nhập mới so với giá trung bình 3 tháng trước.
          Dòng cảnh báo đỏ báo hiệu biên độ đổi giá vượt ngưỡng.
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
            </tr>
          </thead>

          <tbody data-price-variance-table-body>
            <tr>
              <td class="partner-empty-row" colspan="4">Đang tải cảnh báo biến động giá nhập...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/client/js/ceo/partner-vendor-management.js') }}?v={{ time() }}"></script>
@endpush
