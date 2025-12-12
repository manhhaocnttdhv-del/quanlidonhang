<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Route::query();

        if ($request->has('from_province')) {
            $query->where('from_province', $request->from_province);
        }

        if ($request->has('to_province')) {
            $query->where('to_province', $request->to_province);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $routes = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($routes);
        }
        
        return view('admin.routes.index', compact('routes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'from_province' => 'nullable|string|max:255',
            'from_district' => 'nullable|string|max:255',
            'to_province' => 'nullable|string|max:255',
            'to_district' => 'nullable|string|max:255',
            'estimated_days' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $code = $this->generateRouteCode();

        $route = Route::create([
            ...$validated,
            'code' => $code,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tuyến đã được tạo',
                'data' => $route,
            ], 201);
        }
        
        return redirect()->route('admin.routes.index')->with('success', 'Tuyến đã được tạo');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $route = Route::with(['orders'])->findOrFail($id);
        return response()->json($route);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $route = Route::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'from_province' => 'nullable|string|max:255',
            'from_district' => 'nullable|string|max:255',
            'to_province' => 'nullable|string|max:255',
            'to_district' => 'nullable|string|max:255',
            'estimated_days' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $route->update($validated);

        return response()->json([
            'message' => 'Tuyến đã được cập nhật',
            'data' => $route->fresh(),
        ]);
    }

    /**
     * Generate unique route code
     */
    private function generateRouteCode(): string
    {
        do {
            $code = 'TUYEN' . strtoupper(Str::random(6));
        } while (Route::where('code', $code)->exists());

        return $code;
    }
}
