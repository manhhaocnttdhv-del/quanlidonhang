<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'address',
        'province',
        'district',
        'ward',
        'phone',
        'manager_name',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function transactions()
    {
        return $this->hasMany(WarehouseTransaction::class);
    }

    public function orderStatuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get default warehouse (Nghệ An)
     */
    public static function getDefaultWarehouse()
    {
        // Tìm kho Nghệ An - ưu tiên tìm theo province trước
        $ngheAnWarehouse = static::where('is_active', true)
            ->where(function($query) {
                $query->where('province', 'Nghệ An')
                      ->orWhere('name', 'like', '%Nghệ An%')
                      ->orWhere('name', 'like', '%Nghe An%')
                      ->orWhere('code', 'like', '%NA%');
            })
            ->orderByRaw("CASE WHEN province = 'Nghệ An' THEN 0 WHEN name LIKE '%Nghệ An%' THEN 1 ELSE 2 END")
            ->first();
        
        // Nếu không tìm thấy kho Nghệ An, tìm kho đầu tiên có is_active = true
        return $ngheAnWarehouse ?? static::where('is_active', true)->orderBy('id')->first();
    }
}
