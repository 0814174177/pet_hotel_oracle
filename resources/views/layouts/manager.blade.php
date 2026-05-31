<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manager Dashboard')</title>

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
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/client/js/core/dashboard-engine.js') }}?v={{ time() }}"></script>

@stack('scripts')

</body>
</html>
