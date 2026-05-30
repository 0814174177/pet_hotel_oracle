<div class="ceo-sidebar-inner">
    <div class="ceo-sidebar-header">
        <div class="ceo-sidebar-logo">
            <span class="ceo-sidebar-logo-icon">🐾</span>
            <span class="ceo-sidebar-logo-text">Pet Hotel</span>
        </div>

        <button type="button" class="ceo-sidebar-toggle" onclick="toggleCeoSidebar()">
            ☰
        </button>
    </div>

    <nav class="ceo-sidebar-nav">
        <a href="{{ route('ceo.dashboard') }}" class="ceo-sidebar-link">
            <span class="ceo-sidebar-link-icon">📊</span>
            <span class="ceo-sidebar-link-text">Tổng quan</span>
        </a>

        <a href="{{ route('ceo.branches') }}" class="ceo-sidebar-link">
            <span class="ceo-sidebar-link-icon">🏢</span>
            <span class="ceo-sidebar-link-text">Chi nhánh</span>
        </a>

        <a href="{{ route('ceo.service') }}" class="ceo-sidebar-link">
            <span class="ceo-sidebar-link-icon">🧼</span>
            <span class="ceo-sidebar-link-text">Dịch vụ</span>
        </a>

        <a href="{{ route('ceo.finance') }}" class="ceo-sidebar-link">
            <span class="ceo-sidebar-link-icon">💰</span>
            <span class="ceo-sidebar-link-text">Tài chính</span>
        </a>

        <a href="{{ route('ceo.vendors') }}" class="ceo-sidebar-link">
            <span class="ceo-sidebar-link-icon">🚚</span>
            <span class="ceo-sidebar-link-text">Đối tác</span>
        </a>
    </nav>

    <div class="ceo-sidebar-footer">
        <span class="ceo-sidebar-link-icon">👤</span>
        <span class="ceo-sidebar-link-text">CEO Admin</span>
    </div>
</div>
