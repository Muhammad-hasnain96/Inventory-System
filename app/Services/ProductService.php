<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    /**
     * Get all products with pagination
     */
    public function getAllProducts(int $page = 1, int $perPage = 50, bool $includeInventory = false): LengthAwarePaginator
    {
        $query = Product::active()->orderBy('name');

        if ($includeInventory) {
            if (auth()->user()->isSuperAdmin()) {
                // Super admin gets ALL inventories from all branches
                $query->with('inventories');
            } else {
                $branchId = auth()->user()->branch_id;
                $query->with(['inventories' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                }]);
            }
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Search products by name or SKU
     */
    public function searchProducts(string $query, int $page = 1, int $perPage = 50, bool $includeInventory = false): LengthAwarePaginator
    {
        $queryBuilder = Product::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->orderBy('name');

        if ($includeInventory) {
            if (auth()->user()->isSuperAdmin()) {
                // Super admin gets ALL inventories from all branches
                $queryBuilder->with('inventories');
            } else {
                $branchId = auth()->user()->branch_id;
                $queryBuilder->with(['inventories' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                }]);
            }
        }

        return $queryBuilder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get product by ID
     */
    public function getProduct(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Get product by SKU
     */
    public function getProductBySku(string $sku): ?Product
    {
        return Product::bySku($sku)->first();
    }

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        // Validate SKU uniqueness
        if (Product::withTrashed()->bySku($data['sku'])->exists()) {
            throw new \Exception('SKU already exists or has been deleted');
        }

        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            $branch = auth()->user()->branch ?? Branch::active()->first();
            if ($branch) {
                $this->inventoryService->getOrCreateInventory($product, $branch);
            }

            return $product;
        });
    }

    /**
     * Update product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        // If SKU is being changed, validate it's unique
        if (isset($data['sku']) && $data['sku'] !== $product->sku) {
            if (Product::withTrashed()->bySku($data['sku'])->exists()) {
                throw new \Exception('SKU already exists or has been deleted');
            }
        }

        $product->update($data);
        return $product->refresh();
    }

    /**
     * Soft delete product
     */
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Force delete product (permanent deletion)
     * Only allowed if product has no orders or stock transfers
     */
    public function forceDeleteProduct(Product $product): bool
    {
        // Check if product has any order items
        if ($product->orderItems()->exists()) {
            throw new \Exception('Cannot delete product that has been ordered. Product has order history.');
        }

        // Check if product has any stock transfers
        if ($product->stockTransfers()->exists()) {
            throw new \Exception('Cannot delete product that has been transferred between branches.');
        }

        // If no dependencies, force delete the product
        // This will cascade delete inventories and their stock movements
        return $product->forceDelete();
    }

    /**
     * Restore soft-deleted product
     */
    public function restoreProduct(Product $product): bool
    {
        return $product->restore();
    }

    /**
     * Update product status
     */
    public function updateStatus(Product $product, string $status): Product
    {
        $product->update(['status' => $status]);
        return $product->refresh();
    }

    /**
     * Get products with low stock across all branches
     */
    public function getLowStockProducts(): array
    {
        return Product::active()
            ->with(['inventories' => function ($query) {
                $query->lowStock();
            }])
            ->get()
            ->toArray();
    }
}
