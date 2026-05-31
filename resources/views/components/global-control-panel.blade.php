@props([
'title' => 'Dashboard',
'period' => 'month', // Chuyển giá trị mặc định sang key tiếng Anh
'lastUpdate' => '08:30 - Cập nhật thành công',
])

{{-- Load CSS --}}
@once
@push('styles')
{{-- Khuyến nghị dùng Vite hoặc Mix thay vì time() cho dự án thực tế --}}
<link rel="stylesheet" href="{{ asset('assets/client/css/components/global-control-panel.css') }}">
@endpush
@endonce

{{-- Load JS --}}
@once
@push('scripts')
{{-- Hàm asset() sẽ tự động tạo URL tuyệt đối trỏ vào thư mục public của dự án --}}
@endpush
@endonce

@php
// Bước 1: Chuẩn hóa key theo tiêu chuẩn Backend Enum
$periods = [
'day' => 'Ngày',
'month' => 'Tháng',
'year' => 'Năm'
];
@endphp

<div {{ $attributes->merge(['class' => 'global-control-panel']) }}>
  <div>
    <h1 class="global-control-panel__title">{{ $title }}</h1>

    <p class="global-control-panel__last-update">
      <span class="global-control-panel__clock">⏱</span>
      <span>{{ $lastUpdate }}</span>
    </p>
  </div>

  <div class="global-control-panel__period-group">
    <span class="global-control-panel__period-label">Kỳ báo cáo:</span>

    <div class="global-control-panel__date-picker-group">
      <input type="date" class="js-start-date global-control-panel__date-input" name="start_date">

      <span class="global-control-panel__date-separator">-</span>

      <input type="date" class="js-end-date global-control-panel__date-input" name="end_date">

      <button type="button" class="js-apply-filter global-control-panel__apply-btn">
        Lọc
      </button>
    </div>
  </div>
</div>
