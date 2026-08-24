<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddStockRequest;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\TransferStockRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get branch inventory
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $branchId = $request->get('branch_id');

        try {
            if (auth()->user()->isSuperAdmin()) {
                $inventoryQuery = Inventory::with('product', 'branch')
                    ->whereHas('product')
                    ->orderBy('branch_id')
                    ->orderBy('quantity', 'asc');

                if ($branchId) {
                    $inventoryQuery->where('branch_id', $branchId);
                }

                $inventory = $inventoryQuery->paginate(15, ['*'], 'page', $page);
            } else {
                $branch = auth()->user()->branch;
                $inventory = $this->inventoryService->getBranchInventory($branch, $page);
            }

            return response()->json([
                'success' => true,
                'data' => $inventory->items(),
                'pagination' => [
                    'total' => $inventory->total(),
                    'per_page' => $inventory->perPage(),
                    'current_page' => $inventory->currentPage(),
                    'last_page' => $inventory->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            if (auth()->user()->isSuperAdmin()) {
                $branchId = $request->get('branch_id');

                $lowStockQuery = Inventory::with('product', 'branch')
                    ->whereHas('product')
                    ->whereRaw('quantity <= low_stock_threshold')
                    ->orderBy('quantity', 'asc');

                if ($branchId) {
                    $lowStockQuery->where('branch_id', $branchId);
                }

                $items = $lowStockQuery->get();
            } else {
                $branch = auth()->user()->branch;
                $items = $this->inventoryService->getLowStockItems($branch);
            }

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add stock to inventory
     */
    public function addStock(AddStockRequest $request, Inventory $inventory): JsonResponse
    {
        try {
            $this->authorize('addStock', $inventory);

            $movement = $this->inventoryService->addStock(
                $inventory,
                $request->quantity,
                auth()->user(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully',
                'data' => [
                    'inventory' => $inventory->refresh()->load('product'),
                    'movement' => $movement,
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to add stock',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Adjust stock (increase or decrease)
     */
    public function adjustStock(AdjustStockRequest $request, Inventory $inventory): JsonResponse
    {
        try {
            $this->authorize('adjustStock', $inventory);

            $movement = $this->inventoryService->adjustStock(
                $inventory,
                $request->quantity,
                auth()->user(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'data' => [
                    'inventory' => $inventory->refresh()->load('product'),
                    'movement' => $movement,
                ],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to adjust stock',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Transfer stock between branches
     */
    public function transfer(TransferStockRequest $request, Inventory $inventory): JsonResponse
    {
        $fromBranch = $inventory->branch;
        $toBranch = Branch::find($request->to_branch_id);

        if (!$toBranch) {
            return response()->json([
                'success' => false,
                'message' => 'Destination branch not found',
            ], 404);
        }

        try {
            $this->authorize('transfer', $inventory);

            $transfer = $this->inventoryService->transferStock(
                $inventory->product,
                $fromBranch,
                $toBranch,
                $request->quantity,
                auth()->user(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully',
                'data' => $transfer,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to transfer stock',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get stock movement history
     */
    public function movementHistory(Inventory $inventory): JsonResponse
    {
        try {
            $this->authorize('view', $inventory);

            $history = $this->inventoryService->getStockMovementHistory($inventory);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
    }
}
