<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CodReconciliation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reconciliation_number',
        'customer_id',
        'from_date',
        'to_date',
        'total_cod_amount',
        'total_shipping_fee',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'total_cod_amount' => 'decimal:2',
        'total_shipping_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'cod_reconciliation_orders')
            ->withPivot('cod_amount', 'shipping_fee')
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
