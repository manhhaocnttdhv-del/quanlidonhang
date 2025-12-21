@php
    $customer = Auth::guard('customer')->user();
    if (!$customer) {
        $user = Auth::guard('web')->user();
        if ($user && $user->isCustomer()) {
            $customer = $user->customer;
        }
    }
@endphp

<header class="header">
    <div class="d-flex align-items-center">
        <h5 class="mb-0">@yield('page-title', 'Trang chủ')</h5>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted">
            <i class="fas fa-user me-1"></i>
            {{ $customer->name ?? 'Khách hàng' }}
        </span>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i>Đăng xuất
            </button>
        </form>
    </div>
</header>

