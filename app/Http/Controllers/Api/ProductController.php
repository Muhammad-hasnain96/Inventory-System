<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all products with search capability
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view', Product::class);

        $query = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $includeInventory = $request->boolean('include_inventory');

        if ($query) {
            $products = $this->productService->searchProducts($query, $page, $perPage, includeInventory: $includeInventory);
        } else {
            $products = $this->productService->getAllProducts($page, $perPage, includeInventory: $includeInventory);
        }

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single product
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', Product::class);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Create a new product
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        try {
            $product = $this->productService->createProduct($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a product
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        try {
            $product = $this->productService->updateProduct($product, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a product permanently and cleanup related inventory
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        try {
            $this->productService->forceDeleteProduct($product);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update product status
     */
    public function updateStatus(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $request->validate(['status' => 'required|in:active,inactive']);

        try {
            $product = $this->productService->updateStatus($product, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Product status updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Force delete a product permanently
     */
    public function forceDelete(Product $product): JsonResponse
    {
        $this->authorize('forceDelete', $product);

        try {
            $this->productService->forceDeleteProduct($product);

            return response()->json([
                'success' => true,
                'message' => 'Product permanently deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get low stock products
     */
    public function lowStockProducts(): JsonResponse
    {
        $this->authorize('view', Product::class);

        $products = $this->productService->getLowStockProducts();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
