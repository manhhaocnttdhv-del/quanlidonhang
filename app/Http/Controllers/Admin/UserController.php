<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Chỉ super admin mới được xem danh sách users
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.dashboard')->with('error', 'Bạn không có quyền truy cập');
        }

        $query = User::with('warehouse');

        // Filter theo role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filter theo kho
        if ($request->has('warehouse_id') && $request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json($users);
        }

        return view('admin.users.index', compact('users', 'warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Chỉ super admin mới được tạo user
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.dashboard')->with('error', 'Bạn không có quyền tạo nhân viên');
        }

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.create', compact('warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Chỉ super admin mới được tạo user
        if (!auth()->user()->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền tạo nhân viên'], 403);
            }
            return redirect()->back()->with('error', 'Bạn không có quyền tạo nhân viên');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:warehouse_admin,admin,manager,dispatcher,warehouse_staff,staff',
            'phone' => 'nullable|string|max:20',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // Nếu là warehouse_admin thì bắt buộc phải có warehouse_id
        if ($validated['role'] === 'warehouse_admin' && empty($validated['warehouse_id'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['warehouse_id' => 'Vui lòng chọn kho cho admin kho']);
        }

        // Nếu không phải warehouse_admin thì không cần warehouse_id
        if ($validated['role'] !== 'warehouse_admin') {
            $validated['warehouse_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Nhân viên đã được tạo thành công',
                'data' => $user->load('warehouse'),
            ], 201);
        }

        return redirect()->route('admin.users.index')->with('success', 'Nhân viên đã được tạo thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = User::with('warehouse')->findOrFail($id);
        
        if ($request->expectsJson()) {
            return response()->json($user);
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Chỉ super admin mới được sửa user
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.dashboard')->with('error', 'Bạn không có quyền sửa nhân viên');
        }

        $user = User::findOrFail($id);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Chỉ super admin mới được sửa user
        if (!auth()->user()->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền sửa nhân viên'], 403);
            }
            return redirect()->back()->with('error', 'Bạn không có quyền sửa nhân viên');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|in:warehouse_admin,admin,manager,dispatcher,warehouse_staff,staff',
            'phone' => 'nullable|string|max:20',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // Nếu là warehouse_admin thì bắt buộc phải có warehouse_id
        if (isset($validated['role']) && $validated['role'] === 'warehouse_admin' && empty($validated['warehouse_id'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['warehouse_id' => 'Vui lòng chọn kho cho admin kho']);
        }

        // Nếu không phải warehouse_admin thì không cần warehouse_id
        if (isset($validated['role']) && $validated['role'] !== 'warehouse_admin') {
            $validated['warehouse_id'] = null;
        }

        // Nếu đổi role từ warehouse_admin sang role khác, xóa warehouse_id
        if ($user->role === 'warehouse_admin' && isset($validated['role']) && $validated['role'] !== 'warehouse_admin') {
            $validated['warehouse_id'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Nhân viên đã được cập nhật',
                'data' => $user->fresh('warehouse'),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Nhân viên đã được cập nhật');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Chỉ super admin mới được xóa user
        if (!auth()->user()->isSuperAdmin()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền xóa nhân viên'], 403);
            }
            return redirect()->back()->with('error', 'Bạn không có quyền xóa nhân viên');
        }

        $user = User::findOrFail($id);

        // Không cho phép xóa chính mình
        if ($user->id === auth()->id()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Bạn không thể xóa chính mình'], 400);
            }
            return redirect()->back()->with('error', 'Bạn không thể xóa chính mình');
        }

        $user->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Nhân viên đã được xóa']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Nhân viên đã được xóa');
    }
}
