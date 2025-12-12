<?php

namespace App\Http\Controllers;

use App\Models\ShippingFee;
use Illuminate\Http\Request;

class ShippingFeeController extends Controller
{
    /**
     * Display shipping fees index page
     */
    public function index(Request $request)
    {
        $shippingFees = ShippingFee::where('is_active', true)->get();
        
        if ($request->expectsJson()) {
            return response()->json($shippingFees);
        }
        
        return view('admin.shipping-fees.index', compact('shippingFees'));
    }
    
    /**
     * Calculate shipping fee
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'from_province' => 'required|string',
            'from_district' => 'nullable|string',
            'to_province' => 'required|string',
            'to_district' => 'nullable|string',
            'weight' => 'required|numeric|min:0',
            'service_type' => 'required|in:express,standard,economy',
            'cod_amount' => 'nullable|numeric|min:0',
        ]);

        // Priority 1: Find exact match (province + district)
        $shippingFee = ShippingFee::where('from_province', $validated['from_province'])
            ->where('from_district', $validated['from_district'] ?? '')
            ->where('to_province', $validated['to_province'])
            ->where('to_district', $validated['to_district'] ?? '')
            ->where('service_type', $validated['service_type'])
            ->where('is_active', true)
            ->first();

        // Priority 2: Find by province only (same province or different province)
        if (!$shippingFee) {
            $shippingFee = ShippingFee::where('from_province', $validated['from_province'])
                ->where(function($query) use ($validated) {
                    $query->whereNull('from_district')
                          ->orWhere('from_district', '');
                })
                ->where('to_province', $validated['to_province'])
                ->where(function($query) use ($validated) {
                    $query->whereNull('to_district')
                          ->orWhere('to_district', '');
                })
                ->where('service_type', $validated['service_type'])
                ->where('is_active', true)
                ->first();
        }

        // Priority 3: Find by region (same region = same price)
        if (!$shippingFee) {
            $fromRegion = $this->getRegion($validated['from_province']);
            $toRegion = $this->getRegion($validated['to_province']);
            
            // Log for debugging (can remove later)
            \Log::info('Calculating fee by region', [
                'from_province' => $validated['from_province'],
                'to_province' => $validated['to_province'],
                'from_region' => $fromRegion,
                'to_region' => $toRegion,
                'service_type' => $validated['service_type']
            ]);
            
            if ($fromRegion === $toRegion) {
                // Same region - use regional pricing
                $shippingFee = $this->getRegionalFee($fromRegion, $validated['service_type']);
            } else {
                // Different regions - use inter-regional pricing
                $shippingFee = $this->getInterRegionalFee($fromRegion, $toRegion, $validated['service_type']);
            }
        }

        // Priority 4: Default fee based on service type
        if (!$shippingFee) {
            $defaultFees = [
                'express' => 50000,
                'standard' => 30000,
                'economy' => 20000,
            ];
            
            $estimatedFee = $defaultFees[$validated['service_type']] ?? 30000;
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Không tìm thấy bảng cước phù hợp, sử dụng phí ước tính',
                    'estimated_fee' => $estimatedFee,
                    'base_fee' => $estimatedFee,
                    'weight_fee' => 0,
                    'cod_fee' => 0,
                    'total_fee' => $estimatedFee,
                ]);
            }
            return response()->json([
                'message' => 'Không tìm thấy bảng cước phù hợp',
                'estimated_fee' => $estimatedFee,
            ]);
        }

        // Handle both object (from DB) and stdClass (from regional fee) access
        if (is_object($shippingFee)) {
            $baseFee = $shippingFee->base_fee ?? 0;
            $weightFeePerKg = $shippingFee->weight_fee_per_kg ?? 0;
            $codFeePercent = $shippingFee->cod_fee_percent ?? 0;
            $minWeight = $shippingFee->min_weight ?? 0.5;
        } else {
            $baseFee = $shippingFee['base_fee'] ?? 0;
            $weightFeePerKg = $shippingFee['weight_fee_per_kg'] ?? 0;
            $codFeePercent = $shippingFee['cod_fee_percent'] ?? 0;
            $minWeight = $shippingFee['min_weight'] ?? 0.5;
        }
        
        $weightFee = max(0, $validated['weight'] - $minWeight) * $weightFeePerKg;
        $codFee = ($validated['cod_amount'] ?? 0) * ($codFeePercent / 100);
        $totalFee = $baseFee + $weightFee + $codFee;
        
        $fromRegion = $this->getRegion($validated['from_province']);
        $toRegion = $this->getRegion($validated['to_province']);

        return response()->json([
            'base_fee' => $baseFee,
            'weight_fee' => $weightFee,
            'cod_fee' => $codFee,
            'total_fee' => $totalFee,
            'from_region' => $fromRegion,
            'to_region' => $toRegion,
            'calculation_method' => $fromRegion === $toRegion ? 'same_region' : 'inter_region',
            'from_province' => $validated['from_province'],
            'to_province' => $validated['to_province'],
        ]);
    }

    /**
     * Create shipping fee rule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_province' => 'required|string',
            'from_district' => 'nullable|string',
            'to_province' => 'required|string',
            'to_district' => 'nullable|string',
            'service_type' => 'required|in:express,standard,economy',
            'base_fee' => 'required|numeric|min:0',
            'weight_fee_per_kg' => 'required|numeric|min:0',
            'cod_fee_percent' => 'nullable|numeric|min:0|max:100',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
        ]);

        $shippingFee = ShippingFee::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bảng cước đã được tạo',
                'data' => $shippingFee,
            ], 201);
        }
        
        return redirect()->back()->with('success', 'Bảng cước đã được tạo');
    }

    /**
     * Get region of province
     */
    private function getRegion(string $province): string
    {
        // Normalize province name (remove "Thành phố", "Tỉnh" prefix)
        $normalizedProvince = trim(preg_replace('/^(Thành phố|Tỉnh)\s+/', '', $province));
        
        $northernProvinces = [
            'Hà Nội', 'Hải Phòng', 'Hà Giang', 'Cao Bằng', 'Bắc Kạn', 'Tuyên Quang', 
            'Lào Cai', 'Điện Biên', 'Lai Châu', 'Sơn La', 'Yên Bái', 'Hòa Bình', 
            'Thái Nguyên', 'Lạng Sơn', 'Quảng Ninh', 'Bắc Giang', 'Phú Thọ', 
            'Vĩnh Phúc', 'Bắc Ninh', 'Hải Dương', 'Hưng Yên', 'Thái Bình', 
            'Hà Nam', 'Nam Định', 'Ninh Bình'
        ];
        
        $centralProvinces = [
            'Thanh Hóa', 'Nghệ An', 'Hà Tĩnh', 'Quảng Bình', 'Quảng Trị', 
            'Thừa Thiên Huế', 'Đà Nẵng', 'Quảng Nam', 'Quảng Ngãi', 'Bình Định', 
            'Phú Yên', 'Khánh Hòa', 'Ninh Thuận', 'Bình Thuận', 'Kon Tum', 
            'Gia Lai', 'Đắk Lắk', 'Đắk Nông', 'Lâm Đồng'
        ];
        
        // Check normalized name first
        if (in_array($normalizedProvince, $northernProvinces, true)) {
            return 'north';
        } elseif (in_array($normalizedProvince, $centralProvinces, true)) {
            return 'central';
        }
        
        // Also check original name
        if (in_array($province, $northernProvinces, true)) {
            return 'north';
        } elseif (in_array($province, $centralProvinces, true)) {
            return 'central';
        }
        
        // Default to south if not found
        return 'south';
    }

    /**
     * Get regional shipping fee (same region)
     */
    private function getRegionalFee(string $region, string $serviceType)
    {
        $regionalFees = [
            'north' => [
                'express' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 1.5],
                'standard' => ['base_fee' => 25000, 'weight_fee_per_kg' => 5000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 18000, 'weight_fee_per_kg' => 3000, 'cod_fee_percent' => 1.5],
            ],
            'central' => [
                'express' => ['base_fee' => 40000, 'weight_fee_per_kg' => 8000, 'cod_fee_percent' => 1.8],
                'standard' => ['base_fee' => 28000, 'weight_fee_per_kg' => 6000, 'cod_fee_percent' => 1.8],
                'economy' => ['base_fee' => 20000, 'weight_fee_per_kg' => 4000, 'cod_fee_percent' => 1.8],
            ],
            'south' => [
                'express' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 1.5],
                'standard' => ['base_fee' => 25000, 'weight_fee_per_kg' => 5000, 'cod_fee_percent' => 1.5],
                'economy' => ['base_fee' => 18000, 'weight_fee_per_kg' => 3000, 'cod_fee_percent' => 1.5],
            ],
        ];

        $fee = $regionalFees[$region][$serviceType] ?? null;
        if (!$fee) return null;

        // Create a temporary object-like structure
        return (object) [
            'base_fee' => $fee['base_fee'],
            'weight_fee_per_kg' => $fee['weight_fee_per_kg'],
            'cod_fee_percent' => $fee['cod_fee_percent'],
            'min_weight' => 0.5,
            'max_weight' => 50,
        ];
    }

    /**
     * Get inter-regional shipping fee (different regions)
     */
    private function getInterRegionalFee(string $fromRegion, string $toRegion, string $serviceType)
    {
        // Same region already handled
        if ($fromRegion === $toRegion) {
            return $this->getRegionalFee($fromRegion, $serviceType);
        }

        // Adjacent regions (North-Central or Central-South)
        $isAdjacent = 
            ($fromRegion === 'north' && $toRegion === 'central') ||
            ($fromRegion === 'central' && $toRegion === 'north') ||
            ($fromRegion === 'central' && $toRegion === 'south') ||
            ($fromRegion === 'south' && $toRegion === 'central');

        // Far regions (North-South)
        $isFar = 
            ($fromRegion === 'north' && $toRegion === 'south') ||
            ($fromRegion === 'south' && $toRegion === 'north');

        if ($isAdjacent) {
            $fees = [
                'express' => ['base_fee' => 60000, 'weight_fee_per_kg' => 12000, 'cod_fee_percent' => 2],
                'standard' => ['base_fee' => 45000, 'weight_fee_per_kg' => 9000, 'cod_fee_percent' => 2],
                'economy' => ['base_fee' => 35000, 'weight_fee_per_kg' => 7000, 'cod_fee_percent' => 2],
            ];
        } elseif ($isFar) {
            $fees = [
                'express' => ['base_fee' => 80000, 'weight_fee_per_kg' => 15000, 'cod_fee_percent' => 2.5],
                'standard' => ['base_fee' => 60000, 'weight_fee_per_kg' => 12000, 'cod_fee_percent' => 2.5],
                'economy' => ['base_fee' => 45000, 'weight_fee_per_kg' => 9000, 'cod_fee_percent' => 2.5],
            ];
        } else {
            return null;
        }

        $fee = $fees[$serviceType] ?? null;
        if (!$fee) return null;

        return (object) [
            'base_fee' => $fee['base_fee'],
            'weight_fee_per_kg' => $fee['weight_fee_per_kg'],
            'cod_fee_percent' => $fee['cod_fee_percent'],
            'min_weight' => 0.5,
            'max_weight' => 50,
        ];
    }
}
