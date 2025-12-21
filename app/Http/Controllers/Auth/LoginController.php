<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        // Check customer guard
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }
        
        // Check web guard (admin/staff)
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user->isCustomer()) {
                return redirect()->route('customer.dashboard');
            }
            return redirect()->route('admin.dashboard');
        }
        
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $loginField = $request->input('email');
        $password = $request->input('password');
        $remember = $request->filled('remember');

        // Try to login as Customer first (by email or phone)
        // Check email first, then phone
        $customer = \App\Models\Customer::where('email', $loginField)->first();
        
        if (!$customer) {
            $customer = \App\Models\Customer::where('phone', $loginField)->first();
        }
        
        if ($customer) {
            // Check if customer is active
            if (!$customer->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'],
                ]);
            }
            
            // Check if customer has password set
            if (!$customer->password) {
                throw ValidationException::withMessages([
                    'email' => ['Tài khoản chưa được kích hoạt. Vui lòng liên hệ quản trị viên để set mật khẩu.'],
                ]);
            }
            
            // Verify password
            if (Hash::check($password, $customer->password)) {
                // Login as customer
                Auth::guard('customer')->login($customer, $remember);
                $request->session()->regenerate();
                return redirect()->intended(route('customer.dashboard'));
            } else {
                // Password không đúng
                throw ValidationException::withMessages([
                    'password' => ['Mật khẩu không chính xác.'],
                ]);
            }
        }

        // Try to login as User (admin/staff)
        $credentials = [
            'email' => $loginField,
            'password' => $password,
        ];

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();
            
            // Redirect based on role
            if ($user->isCustomer()) {
                return redirect()->intended(route('customer.dashboard'));
            }
            
            return redirect()->intended(route('admin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['Thông tin đăng nhập không chính xác.'],
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        // Logout from both guards
        Auth::guard('customer')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
