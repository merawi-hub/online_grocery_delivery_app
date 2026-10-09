<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DriverTrip;
use App\Models\Order;
use App\Models\Supermarket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Get platform operational overview metrics for the 4th stakeholder (General Operations Manager).
     */
    public function getOverview(): JsonResponse
    {
        $totalStockManagers = User::where('role', 'store_manager')->count();
        $approvedStockManagers = User::where('role', 'store_manager')->where('approval_status', 'approved')->count();
        $pendingStockManagers = User::where('role', 'store_manager')->where('approval_status', 'pending')->count();

        $totalDeliverers = User::where('role', 'driver')->count();
        $approvedDeliverers = User::where('role', 'driver')->where('approval_status', 'approved')->count();
        $pendingDeliverers = User::where('role', 'driver')->where('approval_status', 'pending')->count();

        $totalCustomers = User::where('role', 'customer')->count();
        $totalSupermarkets = Supermarket::count();
        $totalBranches = Branch::count();
        $activeOrders = Order::whereIn('status', ['placed', 'picking', 'packed', 'driver_assigned', 'out_for_delivery'])->count();

        return response()->json([
            'metrics' => [
                'stock_managers' => [
                    'total' => $totalStockManagers,
                    'approved' => $approvedStockManagers,
                    'pending' => $pendingStockManagers,
                ],
                'deliverers' => [
                    'total' => $totalDeliverers,
                    'approved' => $approvedDeliverers,
                    'pending' => $pendingDeliverers,
                ],
                'customers' => [
                    'total' => $totalCustomers,
                ],
                'supermarkets' => [
                    'total' => $totalSupermarkets,
                    'branches' => $totalBranches,
                ],
                'orders' => [
                    'active' => $activeOrders,
                ],
            ],
        ]);
    }

    /**
     * List all Supermarkets & Branches with their assigned stock managers and deliverers.
     */
    public function getBranches(): JsonResponse
    {
        $branches = Branch::with([
            'supermarket',
            'managers:id,name,first_name,last_name,email,phone_number,branch_id,approval_status,is_active',
            'deliverers:id,name,first_name,last_name,email,phone_number,branch_id,vehicle_type,approval_status,is_active',
        ])
        ->withCount(['orders', 'branchProducts'])
        ->get()
        ->map(function ($branch) {
            $pendingOrdersCount = Order::where('branch_id', $branch->id)
                ->whereIn('status', ['placed', 'picking', 'packed', 'driver_assigned', 'out_for_delivery'])
                ->count();

            return [
                'id' => $branch->id,
                'supermarket_id' => $branch->supermarket_id,
                'supermarket_name' => $branch->supermarket ? $branch->supermarket->name : 'FreshMart',
                'supermarket_logo' => $branch->supermarket ? $branch->supermarket->logo_url : null,
                'name' => $branch->name,
                'address' => $branch->address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'contact_phone' => $branch->contact_phone,
                'is_open' => (bool) $branch->is_open,
                'delivery_radius_km' => $branch->delivery_radius_km,
                'total_products' => $branch->branch_products_count,
                'total_orders' => $branch->orders_count,
                'active_orders_count' => $pendingOrdersCount,
                'managers' => $branch->managers,
                'deliverers' => $branch->deliverers,
            ];
        });

        return response()->json([
            'branches' => $branches,
        ]);
    }

    /**
     * List all Stock Managers with branch details.
     */
    public function getStockManagers(): JsonResponse
    {
        $managers = User::where('role', 'store_manager')
            ->with('branch:id,name,address')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'first_name',
                'last_name',
                'phone_number',
                'email',
                'role',
                'branch_id',
                'approval_status',
                'is_active',
                'created_at',
            ]);

        return response()->json([
            'stock_managers' => $managers,
        ]);
    }

    /**
     * Create / Hire a new Stock Manager.
     */
    public function storeStockManager(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
            'approval_status' => ['nullable', Rule::in(['approved', 'pending'])],
        ]);

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $manager = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'store_manager',
            'branch_id' => $validated['branch_id'] ?? null,
            'approval_status' => $validated['approval_status'] ?? 'approved',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Stock Manager created successfully.',
            'stock_manager' => $manager->load('branch:id,name,address'),
        ], 201);
    }

    /**
     * Switch / Reassign a Stock Manager to a new branch, or swap two managers between branches.
     * Core requirement: "switch managers on the stock"
     */
    public function switchStockManagerBranch(Request $request, int $id): JsonResponse
    {
        $manager = User::where('role', 'store_manager')->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'swap_with_manager_id' => 'nullable|exists:users,id',
        ]);

        // Swap mode: exchange branches between two managers
        if (!empty($validated['swap_with_manager_id'])) {
            $otherManager = User::where('role', 'store_manager')->findOrFail($validated['swap_with_manager_id']);

            $tempBranch = $manager->branch_id;
            $manager->branch_id = $otherManager->branch_id;
            $otherManager->branch_id = $tempBranch;

            $manager->save();
            $otherManager->save();

            return response()->json([
                'message' => "Successfully swapped branches between {$manager->name} and {$otherManager->name}.",
                'manager' => $manager->fresh(['branch:id,name,address']),
                'other_manager' => $otherManager->fresh(['branch:id,name,address']),
            ]);
        }

        // Direct branch switch mode
        $newBranchId = $validated['branch_id'] ?? null;
        $manager->branch_id = $newBranchId;
        $manager->save();

        $branchName = $manager->branch ? $manager->branch->name : 'Unassigned Pool';

        return response()->json([
            'message' => "Stock Manager '{$manager->name}' switched to {$branchName}.",
            'stock_manager' => $manager->fresh(['branch:id,name,address']),
        ]);
    }

    /**
     * Approve or update approval status of a Stock Manager.
     */
    public function updateStockManagerApproval(Request $request, int $id): JsonResponse
    {
        $manager = User::where('role', 'store_manager')->findOrFail($id);

        $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'pending', 'rejected'])],
        ]);

        $manager->approval_status = $request->input('approval_status');
        if ($manager->approval_status === 'approved') {
            $manager->is_active = true;
        }
        $manager->save();

        return response()->json([
            'message' => "Stock Manager status updated to {$manager->approval_status}.",
            'stock_manager' => $manager->load('branch:id,name,address'),
        ]);
    }

    /**
     * Remove an existed Stock Manager (delete or revoke).
     */
    public function deleteStockManager(int $id): JsonResponse
    {
        $manager = User::where('role', 'store_manager')->findOrFail($id);

        $name = $manager->name;
        $manager->tokens()->delete();
        $manager->delete();

        return response()->json([
            'message' => "Stock Manager '{$name}' has been successfully removed.",
        ]);
    }

    /**
     * List all Deliverers / Couriers with branch details.
     */
    public function getDeliverers(): JsonResponse
    {
        $deliverers = User::where('role', 'driver')
            ->with('branch:id,name,address')
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'first_name',
                'last_name',
                'phone_number',
                'email',
                'role',
                'branch_id',
                'approval_status',
                'vehicle_type',
                'is_active',
                'created_at',
            ]);

        return response()->json([
            'deliverers' => $deliverers,
        ]);
    }

    /**
     * Create / Hire a new Deliverer / Courier.
     */
    public function storeDeliverer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_type' => 'nullable|string|max:50',
            'approval_status' => ['nullable', Rule::in(['approved', 'pending'])],
        ]);

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $deliverer = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'driver',
            'branch_id' => $validated['branch_id'] ?? null,
            'vehicle_type' => $validated['vehicle_type'] ?? 'Motorbike',
            'approval_status' => $validated['approval_status'] ?? 'approved',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Deliverer created successfully.',
            'deliverer' => $deliverer->load('branch:id,name,address'),
        ], 201);
    }

    /**
     * Switch / Assign a Deliverer to a supermarket branch.
     * Core requirement: "assign deliverers(if needed)"
     */
    public function assignDelivererBranch(Request $request, int $id): JsonResponse
    {
        $deliverer = User::where('role', 'driver')->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $deliverer->branch_id = $validated['branch_id'] ?? null;
        $deliverer->save();

        $branchName = $deliverer->branch ? $deliverer->branch->name : 'Unassigned Fleet Pool';

        return response()->json([
            'message' => "Deliverer '{$deliverer->name}' assigned to {$branchName}.",
            'deliverer' => $deliverer->fresh(['branch:id,name,address']),
        ]);
    }

    /**
     * Assign a Deliverer directly to an active order.
     * Core requirement: "assign deliverers(if needed)"
     */
    public function assignDelivererToOrder(Request $request, int $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);

        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = User::where('role', 'driver')->findOrFail($validated['driver_id']);

        DB::transaction(function () use ($order, $driver) {
            $trip = DriverTrip::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'driver_id' => $driver->id,
                    'status' => 'assigned',
                    'started_at' => now(),
                ]
            );

            // If order was packed or placed, update to driver_assigned
            if (in_array($order->status, ['placed', 'picking', 'packed'])) {
                $order->status = 'driver_assigned';
                $order->save();
            }
        });

        return response()->json([
            'message' => "Deliverer '{$driver->name}' assigned to order #{$order->order_number}.",
            'order' => $order->fresh(['branch', 'driverTrip.driver']),
        ]);
    }

    /**
     * Approve or update approval status of a Deliverer.
     */
    public function updateDelivererApproval(Request $request, int $id): JsonResponse
    {
        $deliverer = User::where('role', 'driver')->findOrFail($id);

        $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'pending', 'rejected'])],
        ]);

        $deliverer->approval_status = $request->input('approval_status');
        if ($deliverer->approval_status === 'approved') {
            $deliverer->is_active = true;
        }
        $deliverer->save();

        return response()->json([
            'message' => "Deliverer status updated to {$deliverer->approval_status}.",
            'deliverer' => $deliverer->load('branch:id,name,address'),
        ]);
    }

    /**
     * Remove an existed Deliverer.
     */
    public function deleteDeliverer(int $id): JsonResponse
    {
        $deliverer = User::where('role', 'driver')->findOrFail($id);

        $name = $deliverer->name;
        $deliverer->tokens()->delete();
        $deliverer->delete();

        return response()->json([
            'message' => "Deliverer '{$name}' has been successfully removed.",
        ]);
    }
}
