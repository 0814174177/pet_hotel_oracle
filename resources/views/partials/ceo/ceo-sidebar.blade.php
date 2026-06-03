<div class="ceo-sidebar-inner">
  <div class="ceo-sidebar-header">
    <div class="ceo-sidebar-logo">
      <img src="{{ asset('images/logo&banner/logo.png') }}" alt="Pet Hotel Logo" class="ceo-sidebar-logo-img">
      <span class="ceo-sidebar-logo-text">Pet Hotel</span>
    </div>

    <button type="button" class="ceo-sidebar-toggle" onclick="toggleCeoSidebar()">
      ☰
    </button>
  </div>

  <nav class="ceo-sidebar-nav">
    <a href="{{ route('ceo.dashboard') }}" class="ceo-sidebar-link {{ request()->routeIs('ceo.index', 'ceo.dashboard') ? 'active' : '' }}">
      <span class="ceo-sidebar-link-text">Tổng quan</span>
    </a>

    <a href="{{ route('ceo.branches') }}" class="ceo-sidebar-link {{ request()->routeIs('ceo.branches') ? 'active' : '' }}">
      <span class="ceo-sidebar-link-text">Chi nhánh</span>
    </a>

    <a href="{{ route('ceo.service') }}" class="ceo-sidebar-link {{ request()->routeIs('ceo.service') ? 'active' : '' }}">
      <span class="ceo-sidebar-link-text">Dịch vụ</span>
    </a>

    <a href="{{ route('ceo.finance') }}" class="ceo-sidebar-link {{ request()->routeIs('ceo.finance') ? 'active' : '' }}">
      <span class="ceo-sidebar-link-text">Tài chính</span>
    </a>

    <a href="{{ route('ceo.vendors') }}" class="ceo-sidebar-link {{ request()->routeIs('ceo.vendors') ? 'active' : '' }}">
      <span class="ceo-sidebar-link-text">Đối tác</span>
    </a>

    <a href="{{ route('ceo.promotions') }}" class="ceo-sidebar-link {{ request()->routeIs('ceo.promotions') ? 'active' : '' }}">
      <span class="ceo-sidebar-link-text">Khuyến mãi</span>
    </a>
  </nav>

  <form action="{{ route('authentication.logout') }}" method="POST" class="ceo-logout-form">
    @csrf
    <button type="submit" class="ceo-logout-btn">
      <span class="ceo-sidebar-link-text">Đăng xuất</span>
    </button>
  </form>
</div>
