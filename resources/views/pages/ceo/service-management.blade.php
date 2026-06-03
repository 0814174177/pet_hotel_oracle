@extends('layouts.ceo')

@section('title', 'Quản trị danh mục dịch vụ')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/client/css/ceo/service-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
$stats = [
[
'key' => 'top-revenue-service',
'title' => 'Dịch vụ doanh thu cao nhất',
'value' => 'Đang tải...',
'trend' => 'Đang tải dữ liệu API',
'isPositive' => true,
'period' => 'kỳ lọc',
],
[
'key' => 'active-service-count',
'title' => 'Dịch vụ đang hoạt động',
'value' => 'Đang tải...',
'trend' => 'Đang tải dữ liệu API',
'isPositive' => true,
'period' => 'kỳ lọc',
],
[
'key' => 'no-revenue-service-count',
'title' => 'Dịch vụ không phát sinh doanh thu',
'value' => 'Đang tải...',
'trend' => 'Đang tải dữ liệu API',
'isPositive' => false,
'period' => 'kỳ lọc',
],
];
@endphp

<div class="ceo-service-page" id="ceoServicePage" data-summary-url="{{ route('api.dashboard.ceo.services.summary') }}"
  data-highest-revenue-url="{{ route('api.dashboard.ceo.services.highest-revenue') }}"
  data-catalog-url="{{ route('api.dashboard.ceo.services.index') }}"
  data-revenue-analysis-url="{{ route('api.dashboard.ceo.services.revenue-analysis') }}"
  data-no-activity-url="{{ route('api.dashboard.ceo.services.no-activity') }}"
  data-most-profitable-url="{{ route('api.dashboard.ceo.services.most-profitable') }}">
  <!-- <header class="ceo-service-header">
    <h1>Quản trị Danh mục Dịch vụ</h1>
    <p>Kiểm soát hiệu suất, biên lợi nhuận và độ phủ dịch vụ trên toàn chuỗi</p>
  </header> -->

  <x-global-control-panel title="Quản trị Danh mục Dịch vụ" />

  <section class="ceo-service-stats">
    @foreach ($stats as $item)
    <x-kpi-card data-kpi="{{ $item['key'] }}" data-kpi-key="{{ $item['key'] }}" :title="$item['title']"
      :value="$item['value']" :trend="$item['trend']" :isPositive="$item['isPositive']" :period="$item['period']"
      :icon="null" />
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
        <caption>Danh sách dịch vụ quản trị toàn chuỗi</caption>
        <thead>
          <tr>
            <th>Mã/Tên/Danh mục</th>
            <th>Trạng thái</th>
            <th>Giá bán / Vốn / Biên lợi nhuận %</th>
            <th class="text-center">Lượt phục vụ (kỳ lọc)</th>
            <th>Độ phủ</th>
            <th>Thao tác quản trị</th>
          </tr>
        </thead>

        <tbody data-service-table-body>
          <tr>
            <td colspan="6" class="service-empty">
              Đang tải dữ liệu dịch vụ...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="ceo-service-grid-wrapper">
      <table class="ceo-service-table">
        <caption>Dịch vụ gánh doanh thu toàn chuỗi</caption>
        <thead>
          <tr>
            <th>Hạng</th>
            <th>Dịch vụ</th>
            <th>Doanh thu dịch vụ</th>
            <th>Tổng doanh thu dịch vụ</th>
            <th>Tỷ trọng</th>
            <th>Mô tả</th>
          </tr>
        </thead>

        <tbody data-highest-revenue-service-table-body>
          <tr>
            <td colspan="6" class="service-empty">
              Đang tải dịch vụ gánh doanh thu...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="ceo-service-grid-wrapper">
      <table class="ceo-service-table">
        <caption>Doanh thu từng dịch vụ toàn chuỗi</caption>
        <thead>
          <tr>
            <th>Hạng</th>
            <th>Dịch vụ</th>
            <th>Doanh thu dịch vụ</th>
            <th>Tỷ trọng</th>
            <th class="text-center">Số đơn</th>
            <th class="text-center">Lượt sử dụng</th>
          </tr>
        </thead>

        <tbody data-service-revenue-table-body>
          <tr>
            <td colspan="6" class="service-empty">
              Đang tải doanh thu từng dịch vụ...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="ceo-service-grid-wrapper">
      <table class="ceo-service-table">
        <caption>Dịch vụ siêu lợi nhuận toàn chuỗi</caption>
        <thead>
          <tr>
            <th>Dịch vụ</th>
            <th>Doanh thu</th>
            <th>Vốn vật tư</th>
            <th>Chi phí nhân công</th>
            <th>Lợi nhuận tạm tính</th>
            <th>Biên lợi nhuận</th>
            <th>Cảnh báo chi phí</th>
          </tr>
        </thead>

        <tbody data-most-profitable-service-table-body>
          <tr>
            <td colspan="7" class="service-empty">
              Đang tải dịch vụ siêu lợi nhuận...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="ceo-service-grid-wrapper">
      <table class="ceo-service-table">
        <caption>Dịch vụ không phát sinh doanh thu trong kỳ</caption>
        <thead>
          <tr>
            <th>Mã/Tên/Danh mục</th>
            <th>Trạng thái</th>
            <th>Giá bán</th>
            <th class="text-center">Lượt đặt lịch trong kỳ</th>
            <th>Lý do không có doanh thu</th>
            <th>Thao tác quản trị</th>
          </tr>
        </thead>

        <tbody data-no-activity-service-table-body>
          <tr>
            <td colspan="6" class="service-empty">
              Đang tải dịch vụ không phát sinh doanh thu...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/client/js/ceo/service-management.js') }}?v={{ time() }}"></script>
@endpush
