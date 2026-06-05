<!DOCTYPE html>
<html lang="vi">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEO Dashboard')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/shared/css/fonts.css') }}">

    {{-- CSS layout --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/layout/ceo-layout.css') }}?v={{ time() }}">

    {{-- CSS component dùng chung --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/components/global-control-panel.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/client/css/components/kpi-card.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/client/css/components/alert-box.css') }}?v={{ time() }}">

    {{-- Chart assets --}}
    <x-chart.assets />

    {{-- CSS riêng của từng page --}}
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/shared/css/no-icons.css') }}?v={{ time() }}">
  </head>

  <body class="app-no-icons">

    <div class="ceo-layout" id="ceoLayout">
      <aside class="ceo-sidebar" id="ceoSidebar">
        @include('partials.ceo.ceo-sidebar')
      </aside>

      <main class="ceo-main-content" id="ceoMainContent">
        <div class="ceo-main-inner">
          @yield('content')
        </div>
      </main>
    </div>

    <script>
    function toggleCeoSidebar() {
      const layout = document.getElementById('ceoLayout');

      if (!layout) {
        return;
      }

      layout.classList.toggle('ceo-layout--collapsed');

      const isCollapsed = layout.classList.contains('ceo-layout--collapsed');
      localStorage.setItem('ceoSidebarCollapsed', isCollapsed ? '1' : '0');
    }

    document.addEventListener('DOMContentLoaded', function() {
      const layout = document.getElementById('ceoLayout');
      const savedState = localStorage.getItem('ceoSidebarCollapsed');

      if (layout && savedState === '1') {
        layout.classList.add('ceo-layout--collapsed');
      }

      document.querySelectorAll('.ceo-layout .global-control-panel__date-picker-group').forEach(function(group) {
        if (group.querySelector('.js-refresh-filter')) {
          return;
        }

        const applyBtn = group.querySelector('.js-apply-filter');

        if (!applyBtn) {
          return;
        }

        const refreshBtn = document.createElement('button');
        refreshBtn.type = 'button';
        refreshBtn.className = 'js-refresh-filter global-control-panel__refresh-btn';
        refreshBtn.textContent = 'Làm mới';
        refreshBtn.addEventListener('click', function() {
          applyBtn.click();
        });

        applyBtn.insertAdjacentElement('afterend', refreshBtn);
      });
    });
    </script>

    {{-- Bổ sung thư viện jQuery để xử lý AJAX --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/client/js/core/dashboard-engine.js') }}?v={{ time() }}"></script>

    @stack('scripts')

  </body>

</html>
