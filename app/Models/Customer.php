<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'password',
        'address',
        'province',
        'district',
        'ward',
        'tax_code',
        'notes',
        'is_active',
        'user_id',
        'warehouse_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function codReconciliations()
    {
        return $this->hasMany(CodReconciliation::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
