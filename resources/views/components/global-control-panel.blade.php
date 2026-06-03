@props([
'title' => 'Dashboard',
'lastUpdate' => null,
'startDate' => null,
'endDate' => null,
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
<script>
(() => {
  const successText = 'Cập nhật thành công';

  function formatCurrentTime() {
    return `${new Intl.DateTimeFormat('vi-VN', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false,
    }).format(new Date())} - ${successText}`;
  }

  function updatePanel(panel) {
    const target = panel.querySelector('[data-global-control-last-update]');

    if (target) {
      target.textContent = formatCurrentTime();
    }
  }

  function updateLastRefresh(root = document) {
    root.querySelectorAll('[data-global-control-panel]').forEach(updatePanel);
  }

  window.GlobalControlPanel = {
    ...(window.GlobalControlPanel || {}),
    formatCurrentTime,
    updateLastRefresh,
  };

  function bindControlPanelEvents() {
    updateLastRefresh();

    document.addEventListener('click', (event) => {
      if (event.target.closest('.js-apply-filter, .js-refresh-filter')) {
        window.setTimeout(() => updateLastRefresh(), 0);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindControlPanelEvents);
  } else {
    bindControlPanelEvents();
  }
})();
</script>
@endpush
@endonce

@php
$lastUpdateText = $lastUpdate
    ?: now(config('app.timezone'))->format('H:i:s') . ' - Cập nhật thành công';
$defaultStartDate = $startDate
    ?: request('start_date')
    ?: now(config('app.timezone'))->subDays(2)->toDateString();
$defaultEndDate = $endDate
    ?: request('end_date')
    ?: now(config('app.timezone'))->subDay()->toDateString();
@endphp

<div {{ $attributes->merge(['class' => 'global-control-panel', 'data-global-control-panel' => true]) }}>
  <div>
    <h1 class="global-control-panel__title">{{ $title }}</h1>

    <p class="global-control-panel__last-update">
      <span class="global-control-panel__clock">⏱</span>
      <span data-global-control-last-update>{{ $lastUpdateText }}</span>
    </p>
  </div>

  <div class="global-control-panel__period-group">
    <span class="global-control-panel__period-label">Thời gian:</span>

    <div class="global-control-panel__date-picker-group">
      <input type="date" class="js-start-date global-control-panel__date-input" name="start_date" value="{{ $defaultStartDate }}">

      <span class="global-control-panel__date-separator">-</span>

      <input type="date" class="js-end-date global-control-panel__date-input" name="end_date" value="{{ $defaultEndDate }}">

      <button type="button" class="js-apply-filter global-control-panel__apply-btn">
        Lọc
      </button>
    </div>
  </div>
</div>
