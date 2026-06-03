<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manager Dashboard')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/shared/css/fonts.css') }}">

    {{-- CSS layout --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/layout/manager-layout.css') }}?v={{ time() }}">

    {{-- CSS component dùng chung --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/components/global-control-panel.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/client/css/components/kpi-card.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/client/css/components/alert-box.css') }}?v={{ time() }}">

    {{-- Chart assets --}}
    <x-chart.assets />

    {{-- CSS riêng từng page --}}
    @stack('styles')
</head>

<body>

<div class="manager-layout" id="managerLayout">
    <aside class="manager-sidebar" id="managerSidebar">
        @include('partials.manager.manager-sidebar')
    </aside>

    <main class="manager-main-content" id="managerMainContent">
        <div class="manager-main-inner">
            @yield('content')
        </div>
    </main>
</div>

<script>
    function toggleManagerSidebar() {
        const layout = document.getElementById('managerLayout');

        if (!layout) {
            return;
        }

        layout.classList.toggle('manager-layout--collapsed');

        const isCollapsed = layout.classList.contains('manager-layout--collapsed');
        localStorage.setItem('managerSidebarCollapsed', isCollapsed ? '1' : '0');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const layout = document.getElementById('managerLayout');
        const savedState = localStorage.getItem('managerSidebarCollapsed');

        if (layout && savedState === '1') {
            layout.classList.add('manager-layout--collapsed');
        }

        document.querySelectorAll('.manager-layout .global-control-panel__date-picker-group').forEach(function(group) {
            if (group.querySelector('.js-manager-refresh-filter')) {
                return;
            }

            const applyBtn = group.querySelector('.js-apply-filter');

            if (!applyBtn) {
                return;
            }

            const refreshBtn = document.createElement('button');
            refreshBtn.type = 'button';
            refreshBtn.className = 'js-manager-refresh-filter manager-layout__refresh-btn';
            refreshBtn.textContent = 'Làm mới';
            refreshBtn.addEventListener('click', function() {
                applyBtn.click();
            });

            applyBtn.insertAdjacentElement('afterend', refreshBtn);
        });
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/client/js/core/dashboard-engine.js') }}?v={{ time() }}"></script>

@stack('scripts')

</body>
</html>
