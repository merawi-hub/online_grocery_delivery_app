<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Get all saved addresses for current user.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($addresses);
    }

    /**
     * Save a new granular address.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|in:home,work,other',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'city' => 'required|string|max:100',
            'district_area' => 'required|string|max:100',
            'street_name' => 'required|string|max:150',
            'house_villa_no' => 'required|string|max:50',
            'building_name_or_no' => 'nullable|string|max:100',
            'floor_number' => 'nullable|string|max:50',
            'apartment_unit' => 'nullable|string|max:50',
            'gate_code' => 'nullable|string|max:50',
            'nearby_landmark' => 'nullable|string|max:255',
            'delivery_instructions' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        // If this is set to default, unset other defaults
        if (! empty($validated['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        } else {
            // If user has no existing addresses, make first one default
            if ($user->addresses()->count() === 0) {
                $validated['is_default'] = true;
            }
        }

        $address = $user->addresses()->create($validated);

        return response()->json([
            'message' => 'Address saved successfully',
            'address' => $address,
        ], 201);
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'label' => 'sometimes|in:home,work,other',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'city' => 'sometimes|string|max:100',
            'district_area' => 'sometimes|string|max:100',
            'street_name' => 'sometimes|string|max:150',
            'house_villa_no' => 'sometimes|string|max:50',
            'building_name_or_no' => 'nullable|string|max:100',
            'floor_number' => 'nullable|string|max:50',
            'apartment_unit' => 'nullable|string|max:50',
            'gate_code' => 'nullable|string|max:50',
            'nearby_landmark' => 'nullable|string|max:255',
            'delivery_instructions' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if (! empty($validated['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address,
        ]);
    }

    /**
     * Delete an address.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json([
            'message' => 'Address removed successfully',
        ]);
    }

    /**
     * Set address as default.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        $user->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default address updated',
            'address' => $address,
        ]);
    }
}
