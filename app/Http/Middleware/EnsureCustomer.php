<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check customer guard first
        if (Auth::guard('customer')->check()) {
            return $next($request);
        }
        
        // Fallback: check web guard with customer role
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user->isCustomer() && $user->customer) {
                return $next($request);
            }
        }
        
        return redirect()->route('login')->with('error', 'Bạn cần đăng nhập với tài khoản khách hàng');
    }
}

