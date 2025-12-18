@php
    $user = auth()->user();
    $isSuperAdmin = $user && $user->isSuperAdmin();
    $isWarehouseAdmin = $user && $user->isWarehouseAdmin();
@endphp

<aside class="sidebar">
    <div class="p-3 mb-3">
        <h4 class="mb-0">
            <i class="fas fa-truck-fast me-2"></i>
            SmartPost
        </h4>
        <small class="text-muted text-white-50">
            @if($isSuperAdmin)
                Admin Tổng
            @elseif($isWarehouseAdmin)
                Admin Kho {{ $user->warehouse->name ?? '' }}
            @else
                Quản lý vận chuyển
            @endif
        </small>
    </div>
    
    <nav class="nav flex-column">
        {{-- <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-home"></i>
            Dashboard
        </a> --}}
        
        @if($isSuperAdmin)
        {{-- ===== MENU CHO SUPER ADMIN (ADMIN TỔNG) ===== --}}
        <hr class="text-white-50 my-2">
        
        <a class="nav-link {{ request()->routeIs('admin.warehouses.create') ? 'active' : '' }}" href="{{ route('admin.warehouses.create') }}">
            <i class="fas fa-plus-circle"></i>
            Tạo kho mới
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="fas fa-user-cog"></i>
            Quản lý nhân viên
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.reports.warehouses-overview') ? 'active' : '' }}" href="{{ route('admin.reports.warehouses-overview') }}">
            <i class="fas fa-chart-line"></i>
            Báo cáo tổng hợp kho
        </a>
        
        @elseif($isWarehouseAdmin)
        {{-- ===== MENU CHO WAREHOUSE ADMIN (ADMIN KHO) ===== --}}
        <hr class="text-white-50 my-2">s
        
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
        
        <hr class="text-white-50 my-2">
        <div class="px-3 py-2 text-white-50 small fw-bold text-uppercase">Quản lý kho</div>
        
        <a class="nav-link {{ request()->routeIs('admin.warehouses.*') ? 'active' : '' }}" href="{{ route('admin.warehouses.index') }}">
            <i class="fas fa-warehouse"></i>
            Kho của tôi
        </a>
        
        <a class="nav-link {{ request()->routeIs('admin.delivery.*') ? 'active' : '' }}" href="{{ route('admin.delivery.index') }}">
            <i class="fas fa-shipping-fast"></i>
            Giao hàng
        </a>
        
        <hr class="text-white-50 my-2">
        <div class="px-3 py-2 text-white-50 small fw-bold text-uppercase">Báo cáo</div>
        
        <a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
            <i class="fas fa-chart-bar"></i>
            Báo cáo kho
        </a>
        
        <hr class="text-white-50 my-2">
        <div class="px-3 py-2 text-white-50 small fw-bold text-uppercase">Quản lý</div>
        
        <a class="nav-link {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}" href="{{ route('admin.drivers.index') }}">
            <i class="fas fa-user-tie"></i>
            Tài xế kho
        </a>
        
        @else
        {{-- ===== MENU CHO CÁC ROLE KHÁC (Admin, Manager, Dispatcher, ...) ===== --}}
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
        
        <hr class="text-white-50 my-2">
        
        <a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
            <i class="fas fa-chart-bar"></i>
            Báo cáo
        </a>
        
        <hr class="text-white-50 my-2">
        
        <a class="nav-link {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}" href="{{ route('admin.drivers.index') }}">
            <i class="fas fa-user-tie"></i>
            Tài xế
        </a>
        @endif
    </nav>
</aside>

