<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverLocation;
use App\Models\DriverTrip;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Get live tracking telemetry for an active order.
     */
    public function trackOrder(Request $request, int $orderId): JsonResponse
    {
        $order = Order::with(['branch', 'driverTrip.driver', 'driverTrip.latestLocation'])
            ->findOrFail($orderId);

        // Security check: only order customer, assigned driver, or admin/manager can view
        $user = $request->user();
        if ($user->role === 'customer' && $order->customer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access to this order.'], 403);
        }

        $trip = $order->driverTrip;
        $latestLocation = $trip ? $trip->latestLocation : null;
        $branch = $order->branch;
        $addressSnapshot = $order->delivery_address_snapshot;

        // Calculate distance and ETA from driver to customer if location exists
        $etaMinutes = null;
        $remainingDistanceKm = null;

        if ($latestLocation && isset($addressSnapshot['latitude'], $addressSnapshot['longitude'])) {
            $destLat = floatval($addressSnapshot['latitude']);
            $destLng = floatval($addressSnapshot['longitude']);

            // Haversine distance
            $earthRadiusKm = 6371;
            $dLat = deg2rad($destLat - $latestLocation->latitude);
            $dLng = deg2rad($destLng - $latestLocation->longitude);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($latestLocation->latitude)) * cos(deg2rad($destLat)) *
                 sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $remainingDistanceKm = round($earthRadiusKm * $c, 2);

            // Estimated driving speed ~ 25 km/h in urban delivery
            $etaMinutes = max(1, (int) round(($remainingDistanceKm / 25) * 60));
        }

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'delivery_otp' => $order->delivery_otp,
            'delivery_type' => $order->delivery_type,
            'store' => [
                'name' => $branch->name,
                'phone' => $branch->contact_phone,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'address' => $branch->address,
            ],
            'customer_destination' => $addressSnapshot,
            'driver' => $trip && $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
                'phone_number' => $trip->driver->phone_number,
                'avatar_url' => $trip->driver->avatar_url,
            ] : null,
            'trip_status' => $trip ? $trip->status : null,
            'telemetry' => $latestLocation ? [
                'latitude' => $latestLocation->latitude,
                'longitude' => $latestLocation->longitude,
                'heading' => $latestLocation->heading,
                'speed' => $latestLocation->speed,
                'updated_at' => $latestLocation->created_at,
            ] : null,
            'remaining_distance_km' => $remainingDistanceKm,
            'eta_minutes' => $etaMinutes,
        ]);
    }

    /**
     * Driver emits current GPS coordinate.
     */
    public function updateDriverLocation(Request $request, int $tripId): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0',
        ]);

        $trip = DriverTrip::findOrFail($tripId);

        // Security check
        if ($request->user()->id !== $trip->driver_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized driver.'], 403);
        }

        $location = DriverLocation::create([
            'trip_id' => $trip->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'heading' => $validated['heading'] ?? 0,
            'speed' => $validated['speed'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Location updated',
            'location' => $location,
        ]);
    }
}
