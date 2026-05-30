<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEO Dashboard')</title>

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
</head>

<body>

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

    document.addEventListener('DOMContentLoaded', function () {
        const layout = document.getElementById('ceoLayout');
        const savedState = localStorage.getItem('ceoSidebarCollapsed');

        if (layout && savedState === '1') {
            layout.classList.add('ceo-layout--collapsed');
        }
    });
</script>

@stack('scripts')

</body>
</html>
