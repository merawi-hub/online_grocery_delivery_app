<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\DriverLocation;
use App\Models\DriverTrip;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supermarket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        $seedUser = function ($criteria, $data) {
            $query = User::query();
            $hasCondition = false;
            
            if (!empty($criteria['email'])) {
                $query->where('email', $criteria['email']);
                $hasCondition = true;
            }
            if (!empty($criteria['phone_number'])) {
                if ($hasCondition) {
                    $query->orWhere('phone_number', $criteria['phone_number']);
                } else {
                    $query->where('phone_number', $criteria['phone_number']);
                    $hasCondition = true;
                }
            }
            if (!empty($criteria['name'])) {
                if ($hasCondition) {
                    $query->orWhere('name', $criteria['name']);
                } else {
                    $query->where('name', $criteria['name']);
                }
            }
            
            $user = $query->first();
            if ($user) {
                $user->update($data);
                return $user;
            }
            return User::create(array_merge($criteria, $data));
        };

        // 1. Create Core Stakeholder Users
        $admin = $seedUser(
            ['name' => 'admin', 'email' => 'admin@grocery.test'],
            [
                'name' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@grocery.test',
                'phone_number' => null,
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'approval_status' => 'approved',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $customerUser1 = $seedUser(
            ['name' => 'user'],
            [
                'name' => 'user',
                'first_name' => 'Customer',
                'last_name' => 'User',
                'email' => 'user@grocery.test',
                'phone_number' => '0969644384',
                'password' => Hash::make('user123'),
                'role' => 'customer',
                'approval_status' => 'approved',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $customer = $customerUser1;

        $manager = $seedUser(
            ['name' => 'manager'],
            [
                'name' => 'manager',
                'first_name' => 'Store',
                'last_name' => 'Manager',
                'email' => 'manager@grocery.test',
                'phone_number' => '0911000002',
                'password' => Hash::make('manager123'),
                'role' => 'store_manager',
                'approval_status' => 'approved',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $managerPending = $seedUser(
            ['email' => 'kidus.stock@grocery.test', 'phone_number' => '0922334455'],
            [
                'name' => 'Kidus Alemu',
                'first_name' => 'Kidus',
                'last_name' => 'Alemu',
                'email' => 'kidus.stock@grocery.test',
                'phone_number' => '0922334455',
                'password' => $defaultPassword,
                'role' => 'store_manager',
                'approval_status' => 'pending',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $driver = $seedUser(
            ['email' => 'driver@grocery.test', 'phone_number' => '0911000004'],
            [
                'name' => 'Yonas Driver',
                'first_name' => 'Yonas',
                'last_name' => 'Driver',
                'email' => 'driver@grocery.test',
                'phone_number' => '0911000004',
                'password' => $defaultPassword,
                'role' => 'driver',
                'approval_status' => 'approved',
                'vehicle_type' => 'Motorbike',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $driverPending = $seedUser(
            ['email' => 'tariku.driver@grocery.test', 'phone_number' => '0933445566'],
            [
                'name' => 'Tariku Bekele',
                'first_name' => 'Tariku',
                'last_name' => 'Bekele',
                'email' => 'tariku.driver@grocery.test',
                'phone_number' => '0933445566',
                'password' => $defaultPassword,
                'role' => 'driver',
                'approval_status' => 'pending',
                'vehicle_type' => 'Bicycle',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $picker = $seedUser(
            ['email' => 'picker@grocery.test', 'phone_number' => '0911000003'],
            [
                'name' => 'Dawit Picker',
                'first_name' => 'Dawit',
                'last_name' => 'Picker',
                'email' => 'picker@grocery.test',
                'phone_number' => '0911000003',
                'password' => $defaultPassword,
                'role' => 'picker',
                'approval_status' => 'approved',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 2. Create Supermarket & Physical Branches (Addis Ababa reference coordinates)
        $supermarket = Supermarket::firstOrCreate(
            ['slug' => 'freshmart-supermarket'],
            [
                'name' => 'FreshMart Supermarket',
                'logo_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=400&q=80',
                'description' => 'Premium fresh organic produce, butcher, bakery, and everyday pantry essentials.',
                'is_active' => true,
            ]
        );

        $branch = Branch::firstOrCreate(
            ['name' => 'Bole Medhanialem Flagship Branch'],
            [
                'supermarket_id' => $supermarket->id,
                'address' => 'Bole Sub-City, Next to Edna Mall, Addis Ababa',
                'latitude' => 9.00160000,
                'longitude' => 38.78450000,
                'delivery_radius_km' => 10.00,
                'contact_phone' => '+251116123456',
                'is_open' => true,
                'opening_time' => '07:30:00',
                'closing_time' => '22:30:00',
            ]
        );

        $branch2 = Branch::firstOrCreate(
            ['name' => 'Kazanchis Express Supermarket'],
            [
                'supermarket_id' => $supermarket->id,
                'address' => 'Kirkos Sub-City, Menelik II Avenue, Addis Ababa',
                'latitude' => 9.01650000,
                'longitude' => 38.76580000,
                'delivery_radius_km' => 8.00,
                'contact_phone' => '+251115123456',
                'is_open' => true,
                'opening_time' => '07:00:00',
                'closing_time' => '23:00:00',
            ]
        );

        $branch3 = Branch::firstOrCreate(
            ['name' => 'Sarbet Premium Supermarket'],
            [
                'supermarket_id' => $supermarket->id,
                'address' => 'Nifas Silk Sub-City, South Africa St, Addis Ababa',
                'latitude' => 8.99500000,
                'longitude' => 38.73800000,
                'delivery_radius_km' => 9.00,
                'contact_phone' => '+251113123456',
                'is_open' => true,
                'opening_time' => '08:00:00',
                'closing_time' => '22:00:00',
            ]
        );

        // Assign initial staff to supermarket branches
        $manager->update(['branch_id' => $branch->id]);
        $managerPending->update(['branch_id' => $branch2->id]);
        $driver->update(['branch_id' => $branch->id]);
        $driverPending->update(['branch_id' => $branch2->id]);

        // 3. Create Granular Precision Address for Customer
        $customerAddress = Address::firstOrCreate(
            ['user_id' => $customer->id, 'label' => 'home'],
            [
                'latitude' => 9.01250000,
                'longitude' => 38.76980000,
                'city' => 'Addis Ababa',
                'district_area' => 'Kirkos / Kazanchis',
                'street_name' => 'Menelik II Avenue',
                'building_name_or_no' => 'Sunrise Luxury Apartments',
                'house_villa_no' => 'B-42',
                'floor_number' => '4th Floor',
                'apartment_unit' => 'Apt 402',
                'gate_code' => '#1984',
                'nearby_landmark' => 'Behind Radisson Blu Hotel, gate with black iron fence',
                'delivery_instructions' => 'Please ring the doorbell. Elevator is available.',
                'is_default' => true,
            ]
        );

        // 4. Create Categories
        $categoriesData = [
            ['name' => 'Fresh Fruits & Vegetables', 'slug' => 'fruits-vegetables', 'icon' => '🥦'],
            ['name' => 'Dairy & Farm Eggs', 'slug' => 'dairy-eggs', 'icon' => '🥛'],
            ['name' => 'Bakery & Pastries', 'slug' => 'bakery-pastries', 'icon' => '🥖'],
            ['name' => 'Butcher & Fresh Meat', 'slug' => 'meat-poultry', 'icon' => '🥩'],
            ['name' => 'Pantry & Beverages', 'slug' => 'pantry-beverages', 'icon' => '☕'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::firstOrCreate(
                ['slug' => $c['slug']],
                ['name' => $c['name'], 'icon_url' => $c['icon'], 'is_active' => true]
            );
        }

        // 5. Products and orders are created dynamically by Store Managers via the Manager portal.
        // Hardcoded products are omitted so only genuine manager-created inventory appears for customers.
    }
}

