<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['order_number', 'branch_id', 'created_by', 'subtotal', 'tax_amount', 'total_amount', 'status', 'notes'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(Branch $branch): string
    {
        $branchCode = strtoupper($branch->code);
        $date = date('Ymd');
        $count = static::whereDate('created_at', now())->count() + 1;
        
        return "{$branchCode}-{$date}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
