<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $primaryKey = 'province_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'province_code',
        'name',
    ];

    /**
     * Get the wards for the province.
     */
    public function wards()
    {
        return $this->hasMany(Ward::class, 'province_code', 'province_code');
    }
}
