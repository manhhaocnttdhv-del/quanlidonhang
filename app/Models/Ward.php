<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $primaryKey = 'ward_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ward_code',
        'ward_name',
        'province_code',
    ];

    /**
     * Get the province that owns the ward.
     */
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }
}
