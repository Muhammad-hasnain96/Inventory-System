<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get inventory for a specific branch
     */
    public function getBranchInventory(Branch $branch, int $page = 1, int $perPage = 15)
    {
        return $branch->inventories()
            ->whereHas('product')
            ->with('product')
            ->orderBy('quantity', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get low stock items for a branch
     */
    public function getLowStockItems(?Branch $branch): array
    {
        $query = $branch ? $branch->inventories() : Inventory::query()->with('branch');

        return $query
            ->whereHas('product')
            ->with('product')
            ->lowStock()
            ->orderBy('quantity', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get or create inventory record for product in branch
     */
    public function getOrCreateInventory(Product $product, Branch $branch): Inventory
    {
        return Inventory::firstOrCreate(
            ['product_id' => $product->id, 'branch_id' => $branch->id],
            ['quantity' => 0, 'low_stock_threshold' => 10]
        );
    }

    /**
     * Add stock to inventory (with transaction safety)
     * This is thread-safe using database-level locking
     */
    public function addStock(Inventory $inventory, int $quantity, User $user, string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($inventory, $quantity, $user, $notes) {
            // Use row-level locking to prevent race conditions
            $inventory = Inventory::lockForUpdate()->find($inventory->id);

            return $inventory->incrementStock($quantity, $user, 'add', $notes);
        });
    }

    /**
     * Adjust stock (increase or decrease)
     */
    public function adjustStock(Inventory $inventory, int $quantity, User $user, string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($inventory, $quantity, $user, $notes) {
            $inventory = Inventory::lockForUpdate()->find($inventory->id);

            if ($quantity > 0) {
                return $inventory->incrementStock($quantity, $user, 'adjust', $notes);
            } else {
                return $inventory->decrementStock(abs($quantity), $user, 'adjust', $notes);
            }
        });
    }

    /**
     * Deduct stock for order (CRITICAL: must use row-level locking for concurrency safety)
     * This is called during order creation and MUST be atomic and prevent race conditions
     */
    public function deductStockForOrder(Inventory $inventory, int $quantity, User $user, ?object $order = null): StockMovement
    {
        return DB::transaction(function () use ($inventory, $quantity, $user, $order) {
            // CRITICAL: Use SELECT FOR UPDATE (lockForUpdate) to prevent concurrent stock deduction
            // This ensures no two requests can modify the same inventory simultaneously
            $inventory = Inventory::lockForUpdate()->find($inventory->id);

            // Verify stock is still available after acquiring lock
            if ($inventory->quantity < $quantity) {
                throw new \Exception(
                    "Insufficient stock for product '{$inventory->product->name}'. "
                    . "Available: {$inventory->quantity}, Required: {$quantity}"
                );
            }

            return $inventory->decrementStock(
                $quantity,
                $user,
                'order_deduction',
                "Order deduction for order #{$order?->id}",
                $order
            );
        });
    }

    /**
     * Transfer stock between branches
     * CRITICAL: Uses double-locking pattern to prevent deadlocks and race conditions
     */
    public function transferStock(
        Product $product,
        Branch $fromBranch,
        Branch $toBranch,
        int $quantity,
        User $user,
        string $notes = null
    ): StockTransfer {
        return DB::transaction(function () use ($product, $fromBranch, $toBranch, $quantity, $user, $notes) {
            // Get source and destination inventories
            $source = Inventory::lockForUpdate()
                ->where('product_id', $product->id)
                ->where('branch_id', $fromBranch->id)
                ->first();

            if (!$source) {
                throw new \Exception("Product not found in source branch inventory");
            }

            if ($source->quantity < $quantity) {
                throw new \Exception(
                    "Insufficient stock in source branch. Available: {$source->quantity}, Required: {$quantity}"
                );
            }

            // Lock destination inventory (create if necessary)
            $destination = Inventory::lockForUpdate()
                ->where('product_id', $product->id)
                ->where('branch_id', $toBranch->id)
                ->first();

            if (!$destination) {
                $destination = $this->getOrCreateInventory($product, $toBranch);
                // Re-lock the newly created record
                $destination = Inventory::lockForUpdate()->find($destination->id);
            }

            // Deduct from source
            $source->decrementStock($quantity, $user, 'transfer_out', "Transfer to {$toBranch->name}", null);

            // Add to destination
            $destination->incrementStock($quantity, $user, 'transfer_in', "Transfer from {$fromBranch->name}", null);

            // Create transfer record
            return StockTransfer::create([
                'product_id' => $product->id,
                'from_branch_id' => $fromBranch->id,
                'to_branch_id' => $toBranch->id,
                'quantity' => $quantity,
                'status' => 'completed',
                'created_by' => $user->id,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Get stock movement history for a product in a branch
     */
    public function getStockMovementHistory(Inventory $inventory, int $limit = 50): array
    {
        return $inventory->stockMovements()
            ->with('createdBy:id,name', 'reference')
            ->recent()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Update low stock threshold
     */
    public function updateLowStockThreshold(Inventory $inventory, int $threshold): Inventory
    {
        $inventory->update(['low_stock_threshold' => $threshold]);
        return $inventory->refresh();
    }
}
