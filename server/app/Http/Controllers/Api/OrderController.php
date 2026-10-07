<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get orders for the logged-in user.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['branch', 'items.product', 'driverTrip.driver'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    /**
     * Place a new grocery order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'address_id' => 'required|exists:addresses,id',
            'delivery_type' => 'required|in:express_immediate,scheduled_slot',
            'scheduled_slot_start' => 'nullable|date',
            'scheduled_slot_end' => 'nullable|date',
            'payment_method' => 'required|in:cash_on_delivery,card_online,wallet',
            'special_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.substitution_preference' => 'required|in:best_match,contact_customer,do_not_substitute',
        ]);

        $user = $request->user();
        $address = $user->addresses()->findOrFail($validated['address_id']);
        $branch = Branch::findOrFail($validated['branch_id']);

        // Check delivery geofence
        if (! $branch->canDeliverTo($address->latitude, $address->longitude)) {
            return response()->json([
                'message' => 'Your delivery address is outside this branch delivery radius (' . $branch->delivery_radius_km . ' km).',
            ], 422);
        }

        return DB::transaction(function () use ($user, $address, $branch, $validated) {
            $subtotal = 0.00;
            $orderItemsData = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $branchProduct = BranchProduct::where('branch_id', $branch->id)
                    ->where('product_id', $product->id)
                    ->first();

                $unitPrice = $branchProduct ? $branchProduct->effective_price : 0.00;
                $quantity = floatval($itemData['quantity']);
                $itemTotal = round($unitPrice * $quantity, 2);
                $subtotal += $itemTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'requested_quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'final_item_total' => $itemTotal,
                    'substitution_preference' => $itemData['substitution_preference'],
                ];
            }

            $deliveryFee = 50.00; // Flat delivery fee for starter
            $estimatedTotal = $subtotal + $deliveryFee;

            // Generate 6-digit delivery OTP for secure handover (Yango style)
            $deliveryOtp = str_pad(strval(random_int(100000, 999999)), 6, '0', STR_PAD_LEFT);

            // Snapshot address with customer contact
            $addressSnapshot = [
                'label' => $address->label,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'city' => $address->city,
                'district_area' => $address->district_area,
                'street_name' => $address->street_name,
                'building_name_or_no' => $address->building_name_or_no,
                'house_villa_no' => $address->house_villa_no,
                'floor_number' => $address->floor_number,
                'apartment_unit' => $address->apartment_unit,
                'gate_code' => $address->gate_code,
                'nearby_landmark' => $address->nearby_landmark,
                'delivery_instructions' => $address->delivery_instructions,
                'customer_phone' => $user->phone_number,
                'customer_name' => $user->name,
            ];

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $user->id,
                'branch_id' => $branch->id,
                'address_id' => $address->id,
                'delivery_address_snapshot' => $addressSnapshot,
                'status' => 'placed',
                'delivery_type' => $validated['delivery_type'],
                'scheduled_slot_start' => $validated['scheduled_slot_start'] ?? null,
                'scheduled_slot_end' => $validated['scheduled_slot_end'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'estimated_total' => $estimatedTotal,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'cash_on_delivery' ? 'pending' : 'authorized',
                'delivery_otp' => $deliveryOtp,
                'special_notes' => $validated['special_notes'] ?? null,
            ]);

            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load(['items.product', 'branch']),
            ], 201);
        });
    }

    /**
     * Show order details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->with(['branch', 'items.product', 'driverTrip.driver', 'driverTrip.latestLocation'])
            ->findOrFail($id);

        return response()->json($order);
    }
}
