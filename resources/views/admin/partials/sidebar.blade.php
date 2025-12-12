<aside class="sidebar">
    <div class="p-3 mb-3">
        <h4 class="mb-0">
            <i class="fas fa-truck-fast me-2"></i>
            SmartPost
        </h4>
        <small class="text-muted">Quản lý vận chuyển</small>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-home"></i>
            Dashboard
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
            <i class="fas fa-users"></i>
            Khách hàng
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
            <i class="fas fa-box"></i>
            Quản lý vận đơn
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.dispatch.*') ? 'active' : '' }}" href="{{ route('admin.dispatch.index') }}">
            <i class="fas fa-route"></i>
            Điều phối nhận
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.warehouses.*') ? 'active' : '' }}" href="{{ route('admin.warehouses.index') }}">
            <i class="fas fa-warehouse"></i>
            Quản lý kho
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.delivery.*') ? 'active' : '' }}" href="{{ route('admin.delivery.index') }}">
            <i class="fas fa-shipping-fast"></i>
            Giao hàng
        </a>
        
        {{-- <a class="nav-link {{ request()->routeIs('admin.tracking.*') ? 'active' : '' }}" href="{{ route('admin.tracking.index') }}">
            <i class="fas fa-search-location"></i>
            Tracking
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.shipping-fees.*') ? 'active' : '' }}" href="{{ route('admin.shipping-fees.index') }}">
            <i class="fas fa-calculator"></i>
            Tra cước
        </a>
         --}}
        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
            <i class="fas fa-chart-bar"></i>
            Báo cáo
        </a>
        
        {{-- <a class="nav-link {{ request()->routeIs('admin.cod-reconciliations.*') ? 'active' : '' }}" href="{{ route('admin.cod-reconciliations.index') }}">
            <i class="fas fa-file-invoice-dollar"></i>
            Bảng kê COD
        </a> --}}
        
        <hr class="text-white-50 my-2">
        
        <a class="nav-link {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}" href="{{ route('admin.drivers.index') }}">
            <i class="fas fa-user-tie"></i>
            Tài xế
        </a>
        
        {{-- <a class="nav-link {{ request()->routeIs('admin.routes.*') ? 'active' : '' }}" href="{{ route('admin.routes.index') }}">
            <i class="fas fa-map-marked-alt"></i>
            Tuyến vận chuyển
        </a> --}}
    </nav>
</aside>

