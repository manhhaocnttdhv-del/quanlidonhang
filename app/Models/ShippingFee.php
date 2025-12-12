<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_province',
        'from_district',
        'to_province',
        'to_district',
        'service_type',
        'base_fee',
        'weight_fee_per_kg',
        'cod_fee_percent',
        'min_weight',
        'max_weight',
        'is_active',
    ];

    protected $casts = [
        'base_fee' => 'decimal:2',
        'weight_fee_per_kg' => 'decimal:2',
        'cod_fee_percent' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
