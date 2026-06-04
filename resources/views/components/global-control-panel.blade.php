@props([
'title' => 'Dashboard',
'period' => 'month', // Chuyển giá trị mặc định sang key tiếng Anh
'lastUpdate' => '08:30 - Cập nhật thành công',
'exportUrl' => null,
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
<script>
  document.addEventListener('click', function (event) {
    const exportButton = event.target.closest('.js-export-excel');

    if (!exportButton) {
      return;
    }

    const panel = exportButton.closest('.global-control-panel');
    const startDate = panel?.querySelector('.js-start-date')?.value;
    const endDate = panel?.querySelector('.js-end-date')?.value;
    const url = new URL(exportButton.dataset.exportUrl, window.location.origin);

    if (startDate) {
      url.searchParams.set('start_date', startDate);
    }

    if (endDate) {
      url.searchParams.set('end_date', endDate);
    }

    exportButton.href = url.toString();
  });
</script>
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

      @if ($exportUrl)
        <a href="{{ $exportUrl }}" class="js-export-excel global-control-panel__export-btn" data-export-url="{{ $exportUrl }}">
          <span class="global-control-panel__export-icon">XLSX</span>
          <span>Xu&#7845;t Excel</span>
        </a>
      @endif
    </div>
  </div>
</div>
