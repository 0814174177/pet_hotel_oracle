<div class="manager-sidebar-inner">
    <div class="manager-sidebar-header">
        <div class="manager-sidebar-logo">
            <span class="manager-sidebar-logo-icon">🐾</span>
            <span class="manager-sidebar-logo-text">Pet Hotel</span>
        </div>

        <button type="button" class="manager-sidebar-toggle" onclick="toggleManagerSidebar()">
            ☰
        </button>
    </div>

    <nav class="manager-sidebar-nav">
        <a href="{{ route('manager.dashboard') }}" class="manager-sidebar-link {{ request()->routeIs('manager.index', 'manager.dashboard') ? 'active' : '' }}">
            <span class="manager-sidebar-link-icon">📊</span>
            <span class="manager-sidebar-link-text">Dashboard</span>
        </a>

        <a href="{{ route('manager.reports') }}" class="manager-sidebar-link {{ request()->routeIs('manager.reports') ? 'active' : '' }}">
            <span class="manager-sidebar-link-icon">📈</span>
            <span class="manager-sidebar-link-text">Doanh thu</span>
        </a>

        <a href="{{ route('manager.service') }}" class="manager-sidebar-link {{ request()->routeIs('manager.service') ? 'active' : '' }}">
            <span class="manager-sidebar-link-icon">🧼</span>
            <span class="manager-sidebar-link-text">Dịch vụ</span>
        </a>

        <a href="{{ route('manager.inventory') }}" class="manager-sidebar-link {{ request()->routeIs('manager.inventory') ? 'active' : '' }}">
            <span class="manager-sidebar-link-icon">📦</span>
            <span class="manager-sidebar-link-text">Vật tư</span>
        </a>
        <a href="{{ route('manager.promotions') }}" class="manager-sidebar-link {{ request()->routeIs('manager.promotions') ? 'active' : '' }}">
            <span class="manager-sidebar-link-icon">%</span>
            <span class="manager-sidebar-link-text">Khuyến mãi</span>
        </a>

        <a href="{{ route('manager.employees') }}" class="manager-sidebar-link {{ request()->routeIs('manager.employees*') ? 'active' : '' }}">
            <span class="manager-sidebar-link-icon">NV</span>
            <span class="manager-sidebar-link-text">Nhân viên</span>
        </a>
    </nav>

    <div class="manager-sidebar-footer">
        <span class="manager-sidebar-link-icon">👤</span>
        <span class="manager-sidebar-link-text">Branch Manager</span>
    </div>
</div>
