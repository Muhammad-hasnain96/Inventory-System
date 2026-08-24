<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $fillable = ['product_id', 'branch_id', 'quantity', 'low_stock_threshold'];

    protected $casts = [
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    /**
     * Safely increment stock with transaction log
     * This method MUST be called within a database transaction
     */
    public function incrementStock(int $quantity, User $user, string $type = 'add', string $notes = null, ?Model $reference = null): StockMovement
    {
        $before = $this->quantity;
        $this->increment('quantity', $quantity);
        $this->refresh();

        $data = [
            'inventory_id' => $this->id,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->quantity,
            'notes' => $notes,
            'created_by' => $user->id,
        ];

        if ($reference) {
            $data['reference_type'] = get_class($reference);
            $data['reference_id'] = $reference->id;
        }

        return StockMovement::create($data);
    }

    /**
     * Safely decrement stock with transaction log
     * This method MUST be called within a database transaction
     */
    public function decrementStock(int $quantity, User $user, string $type = 'adjust', string $notes = null, ?Model $reference = null): StockMovement
    {
        if ($this->quantity < $quantity) {
            throw new \Exception('Insufficient stock available. Current: ' . $this->quantity . ', Required: ' . $quantity);
        }

        $before = $this->quantity;
        $this->decrement('quantity', $quantity);
        $this->refresh();

        $data = [
            'inventory_id' => $this->id,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->quantity,
            'notes' => $notes,
            'created_by' => $user->id,
        ];

        if ($reference) {
            $data['reference_type'] = get_class($reference);
            $data['reference_id'] = $reference->id;
        }

        return StockMovement::create($data);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= low_stock_threshold');
    }
}
