<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'customer_id',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_province',
        'sender_district',
        'sender_ward',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_province',
        'receiver_district',
        'receiver_ward',
        'item_type',
        'weight',
        'length',
        'width',
        'height',
        'cod_amount',
        'cod_collected',
        'shipping_fee',
        'service_type',
        'status',
        'pickup_driver_id',
        'delivery_driver_id',
        'route_id',
        'warehouse_id',
        'to_warehouse_id',
        'pickup_scheduled_at',
        'picked_up_at',
        'delivery_scheduled_at',
        'delivered_at',
        'delivery_notes',
        'failure_reason',
        'is_fragile',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'cod_collected' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'is_fragile' => 'boolean',
        'pickup_scheduled_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pickupDriver()
    {
        return $this->belongsTo(Driver::class, 'pickup_driver_id');
    }

    public function deliveryDriver()
    {
        return $this->belongsTo(Driver::class, 'delivery_driver_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function statuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function warehouseTransactions()
    {
        return $this->hasMany(WarehouseTransaction::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function codReconciliations()
    {
        return $this->belongsToMany(CodReconciliation::class, 'cod_reconciliation_orders')
            ->withPivot('cod_amount', 'shipping_fee')
            ->withTimestamps();
    }
}
