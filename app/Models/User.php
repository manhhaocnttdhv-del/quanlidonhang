<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
        'warehouse_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the warehouse that the user belongs to.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Check if user is warehouse admin
     */
    public function isWarehouseAdmin(): bool
    {
        return $this->role === 'warehouse_admin';
    }

    /**
     * Check if user is admin of a specific warehouse
     */
    public function isAdminOfWarehouse(?int $warehouseId): bool
    {
        return $this->isWarehouseAdmin() && $this->warehouse_id === $warehouseId;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user can manage warehouses (super_admin or admin)
     */
    public function canManageWarehouses(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'admin';
    }

    /**
     * Check if user can access warehouse (super_admin or admin or warehouse_admin of that warehouse)
     */
    public function canAccessWarehouse(?int $warehouseId): bool
    {
        if ($this->isSuperAdmin() || $this->role === 'admin') {
            return true;
        }
        return $this->isAdminOfWarehouse($warehouseId);
    }
}
