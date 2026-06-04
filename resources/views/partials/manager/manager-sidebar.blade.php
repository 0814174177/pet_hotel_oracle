@php
    $managerBranchId = auth()->user()?->managerBranchId();
    $managerDashboardUrl = $managerBranchId
        ? route('manager.branches.dashboard', ['branchId' => $managerBranchId])
        : route('manager.dashboard');
    $managerReportsUrl = $managerBranchId
        ? route('manager.branches.reports', ['branchId' => $managerBranchId])
        : route('manager.reports');
    $managerServiceUrl = $managerBranchId
        ? route('manager.branches.service', ['branchId' => $managerBranchId])
        : route('manager.service');
    $managerInventoryUrl = $managerBranchId
        ? route('manager.branches.inventory', ['branchId' => $managerBranchId])
        : route('manager.inventory');
@endphp

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
        <a
            href="{{ $managerDashboardUrl }}"
            class="manager-sidebar-link {{ request()->routeIs('manager.index', 'manager.dashboard', 'manager.branches.dashboard') ? 'active' : '' }}"
        >
            <span class="manager-sidebar-link-icon">📊</span>
            <span class="manager-sidebar-link-text">Dashboard</span>
        </a>

        <a
            href="{{ $managerReportsUrl }}"
            class="manager-sidebar-link {{ request()->routeIs('manager.reports', 'manager.branches.reports') ? 'active' : '' }}"
        >
            <span class="manager-sidebar-link-icon">📈</span>
            <span class="manager-sidebar-link-text">Doanh thu</span>
        </a>

        <a
            href="{{ $managerServiceUrl }}"
            class="manager-sidebar-link {{ request()->routeIs('manager.service', 'manager.branches.service') ? 'active' : '' }}"
        >
            <span class="manager-sidebar-link-icon">🧼</span>
            <span class="manager-sidebar-link-text">Dịch vụ</span>
        </a>

        <a
            href="{{ $managerInventoryUrl }}"
            class="manager-sidebar-link {{ request()->routeIs('manager.inventory', 'manager.branches.inventory') ? 'active' : '' }}"
        >
            <span class="manager-sidebar-link-icon">📦</span>
            <span class="manager-sidebar-link-text">Vật tư</span>
        </a>

        <a
            href="{{ route('manager.promotions') }}"
            class="manager-sidebar-link {{ request()->routeIs('manager.promotions') ? 'active' : '' }}"
        >
            <span class="manager-sidebar-link-icon">%</span>
            <span class="manager-sidebar-link-text">Khuyến mãi</span>
        </a>

        <a
            href="{{ route('manager.employees') }}"
            class="manager-sidebar-link {{ request()->routeIs('manager.employees*') ? 'active' : '' }}"
        >
            <span class="manager-sidebar-link-icon">NV</span>
            <span class="manager-sidebar-link-text">Nhân viên</span>
        </a>
    </nav>

    <div class="manager-sidebar-footer">
        <div class="manager-sidebar-role">
            <span class="manager-sidebar-role-icon">👤</span>
            <span class="manager-sidebar-link-text">Branch Manager</span>
        </div>

        <form action="{{ route('authentication.logout') }}" method="POST" class="manager-logout-form">
            @csrf
            <button type="submit" class="manager-logout-btn">
                <span class="manager-logout-icon" aria-hidden="true">&#x23FB;</span>
                <span class="manager-sidebar-link-text">Đăng xuất</span>
            </button>
        </form>
    </div>
</div>
