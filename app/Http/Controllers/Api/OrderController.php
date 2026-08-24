<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Check if user can access a specific branch for order creation
     */
    private function canAccessBranch(User $user, int $branchId): bool
    {
        // Super admin can access all branches
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch managers and sales users can place orders in any branch
        if ($user->isBranchManager() || $user->isSalesUser()) {
            return true;
        }

        return false;
    }

    /**
     * Get branch orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->isSalesUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $branch = $user->branch;
        $page = $request->get('page', 1);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not assigned. Please contact administrator.',
            ], 403);
        }

        try {
            $orders = $this->orderService->getBranchOrders($branch, $page);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
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
     * Get a single order
     */
    public function show(Order $order): JsonResponse
    {
        try {
            $this->authorize('view', $order);

            $order = $this->orderService->getOrder($order->id);

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
    }

    /**
     * Create a new order
     * CRITICAL: This handles order creation with race condition prevention
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Order::class);

            $user = auth()->user();
            $selectedBranchId = $request->validated()['branch_id'];
            
            // Check if user has access to the selected branch
            if (!$this->canAccessBranch($user, $selectedBranchId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to the selected branch.',
                ], 403);
            }
            
            $branch = \App\Models\Branch::find($selectedBranchId);
            
            // Call the service which handles all the concurrency logic
            $order = $this->orderService->createOrder(
                $branch,
                $user,
                $request->validated()['items'],
                $request->notes ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to create orders',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Order $order, Request $request): JsonResponse
    {
        try {
            $this->authorize('cancel', $order);

            if ($order->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already cancelled',
                ], 400);
            }

            $reason = $request->get('reason', 'No reason provided');
            $order = $this->orderService->cancelOrder($order, auth()->user(), $reason);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to cancel this order',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
