<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'branch_id', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role->name === $role;
    }

    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is Admin (Super Admin or Branch Manager)
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('super_admin') || $this->hasRole('branch_manager');
    }

    /**
     * Check if user is Branch Manager
     */
    public function isBranchManager(): bool
    {
        return $this->hasRole('branch_manager');
    }

    /**
     * Check if user is Sales User
     */
    public function isSalesUser(): bool
    {
        return $this->hasRole('sales_user');
    }

    /**
     * Get all permissions for the user based on role
     */
    public function getPermissions(): array
    {
        return match ($this->role->name) {
            'super_admin' => [
                'manage_branches',
                'manage_products',
                'manage_inventory',
                'view_reports',
                'manage_users',
            ],
            'branch_manager' => [
                'manage_inventory',
                'create_orders',
                'view_reports',
            ],
            'sales_user' => [
                'create_orders',
            ],
            default => [],
        };
    }

    /**
     * Check if user can perform an action
     */
    public function can($abilities, $arguments = []): bool
    {
        if (is_array($abilities)) {
            return collect($abilities)->every(fn ($ability) => $this->can($ability, $arguments));
        }

        return in_array($abilities, $this->getPermissions());
    }

    /**
     * Check if user cannot perform an action
     */
    public function cannot($ability, $arguments = []): bool
    {
        return !$this->can($ability, $arguments);
    }

    /**
     * Check specific permissions
     */
    public function canManageBranches(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageProducts(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageInventory(): bool
    {
        return $this->isSuperAdmin() || $this->isBranchManager();
    }

    public function canCreateOrders(): bool
    {
        return $this->isBranchManager() || $this->isSalesUser();
    }

    public function canViewReports(): bool
    {
        return $this->isSuperAdmin() || $this->isBranchManager();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
