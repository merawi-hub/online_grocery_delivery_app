<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverLocation;
use App\Models\DriverTrip;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DelivererController extends Controller
{
    /**
     * Get available orders ready for pickup at supermarket branches,
     * plus any orders assigned directly to the current deliverer.
     */
    public function getAvailableOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $driverId = $user ? $user->id : null;
        $driverBranchId = $user ? $user->branch_id : null;

        $query = Order::with(['branch.supermarket', 'items.product', 'driverTrip.driver'])
            ->whereIn('status', ['packed', 'driver_assigned']);

        // If driver has an assigned branch, prioritize or include that branch plus unassigned orders
        if ($driverBranchId) {
            $query->where(function ($q) use ($driverId, $driverBranchId) {
                $q->where('branch_id', $driverBranchId)
                  ->orWhereHas('driverTrip', fn($dt) => $dt->where('driver_id', $driverId));
            });
        }

        $orders = $query->orderByDesc('updated_at')->get();

        return response()->json([
            'available_orders' => $orders,
        ]);
    }

    /**
     * Get the deliverer's currently active in-transit delivery.
     */
    public function getActiveDelivery(Request $request): JsonResponse
    {
        $user = $request->user();
        $driverId = $user ? $user->id : null;

        // Find active order where trip belongs to this driver and status is out_for_delivery
        $activeOrder = Order::with(['branch.supermarket', 'items.product', 'driverTrip.driver', 'driverTrip.latestLocation'])
            ->where('status', 'out_for_delivery')
            ->whereHas('driverTrip', function ($q) use ($driverId) {
                if ($driverId) {
                    $q->where('driver_id', $driverId);
                }
            })
            ->first();

        // If no driver-specific active trip found, fallback to the most recent out_for_delivery order for demo flexibility
        if (!$activeOrder) {
            $activeOrder = Order::with(['branch.supermarket', 'items.product', 'driverTrip.driver', 'driverTrip.latestLocation'])
                ->where('status', 'out_for_delivery')
                ->latest('updated_at')
                ->first();
        }

        return response()->json([
            'active_order' => $activeOrder,
        ]);
    }

    /**
     * Deliverer picks up the order from the supermarket and starts delivery.
     * Core requirement: "deliverer picks on>>>>>deliver"
     */
    public function pickupOrder(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $driverId = $user ? $user->id : null;

        $order = Order::with('branch')->findOrFail($orderId);

        DB::transaction(function () use ($order, $driverId) {
            $order->status = 'out_for_delivery';
            $order->save();

            $trip = DriverTrip::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'driver_id' => $driverId ?? 1,
                    'status' => 'delivering',
                    'started_at' => now(),
                    'distance_km' => 3.50,
                    'duration_minutes' => 15,
                ]
            );

            // Record initial location at store
            if ($order->branch) {
                DriverLocation::create([
                    'trip_id' => $trip->id,
                    'latitude' => $order->branch->latitude,
                    'longitude' => $order->branch->longitude,
                    'heading' => 45.0,
                    'speed' => 15.0,
                ]);
            }
        });

        return response()->json([
            'message' => "Order #{$order->order_number} successfully picked up from supermarket. In transit to customer!",
            'order' => $order->fresh(['branch', 'driverTrip.driver', 'items.product']),
        ]);
    }

    /**
     * Deliverer completes and confirms delivery handover to customer.
     * Core requirement: "delivery confirmed when recieved"
     */
    public function confirmDelivery(Request $request, int $orderId): JsonResponse
    {
        $order = Order::with('driverTrip')->findOrFail($orderId);

        $validated = $request->validate([
            'otp' => 'nullable|string|size:6',
        ]);

        // If OTP provided, optionally verify against order's delivery_otp
        if (!empty($validated['otp']) && !empty($order->delivery_otp)) {
            if ($validated['otp'] !== $order->delivery_otp) {
                return response()->json([
                    'message' => 'Invalid delivery OTP code. Please ask customer for the correct 6-digit code.',
                ], 422);
            }
        }

        DB::transaction(function () use ($order) {
            $order->status = 'delivered';
            if ($order->payment_method === 'cash_on_delivery') {
                $order->payment_status = 'paid';
            }
            $order->final_total = $order->estimated_total;
            $order->save();

            if ($order->driverTrip) {
                $order->driverTrip->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => "Delivery for Order #{$order->order_number} successfully confirmed and completed!",
            'order' => $order->fresh(['branch', 'driverTrip.driver', 'items.product']),
        ]);
    }

    /**
     * Deliverer delivery history and earnings summary.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $driverId = $user ? $user->id : null;

        $query = Order::with(['branch', 'items.product', 'driverTrip'])
            ->where('status', 'delivered');

        if ($driverId) {
            $query->whereHas('driverTrip', fn($q) => $q->where('driver_id', $driverId));
        }

        $deliveredOrders = $query->orderByDesc('updated_at')->get();

        $totalCompleted = $deliveredOrders->count();
        $deliveryFeePerDrop = 50.00;
        $totalEarnings = $totalCompleted * $deliveryFeePerDrop;

        return response()->json([
            'stats' => [
                'total_deliveries' => $totalCompleted,
                'total_earnings' => $totalEarnings,
                'fee_per_drop' => $deliveryFeePerDrop,
                'rating' => 4.9,
            ],
            'history' => $deliveredOrders,
        ]);
    }
}
