<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'license_number',
        'vehicle_type',
        'vehicle_number',
        'area',
        'warehouse_id',
        'driver_type',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function pickupOrders()
    {
        return $this->hasMany(Order::class, 'pickup_driver_id');
    }

    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_driver_id');
    }

    public function orderStatuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    /**
     * Check if driver is shipper
     */
    public function isShipper(): bool
    {
        return $this->driver_type === 'shipper';
    }

    /**
     * Check if driver is intercity driver
     */
    public function isIntercityDriver(): bool
    {
        return $this->driver_type === 'intercity_driver';
    }
}
