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

        // 1. Create Core Stakeholder Users
        $admin = User::firstOrCreate(
            ['phone_number' => '+251911000001'],
            [
                'name' => 'Abebe Admin',
                'email' => 'admin@grocery.test',
                'password' => $defaultPassword,
                'role' => 'admin',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $manager = User::firstOrCreate(
            ['phone_number' => '+251911000002'],
            [
                'name' => 'Sara Manager',
                'email' => 'manager@grocery.test',
                'password' => $defaultPassword,
                'role' => 'store_manager',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $picker = User::firstOrCreate(
            ['phone_number' => '+251911000003'],
            [
                'name' => 'Dawit Picker',
                'email' => 'picker@grocery.test',
                'password' => $defaultPassword,
                'role' => 'picker',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $driver = User::firstOrCreate(
            ['phone_number' => '+251911000004'],
            [
                'name' => 'Yonas Driver',
                'email' => 'driver@grocery.test',
                'password' => $defaultPassword,
                'role' => 'driver',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $customer = User::firstOrCreate(
            ['phone_number' => '+251911000005'],
            [
                'name' => 'Marta Shopper',
                'email' => 'customer@grocery.test',
                'password' => $defaultPassword,
                'role' => 'customer',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // 2. Create Supermarket & Physical Branch (Addis Ababa reference coordinates)
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

        // 5. Create Grocery Products (Weighted Produce vs Fixed-Unit packaged goods)
        $productsData = [
            // Fruits & Veg (Weighted)
            [
                'cat' => 'fruits-vegetables',
                'name' => 'Fresh Cavendish Bananas',
                'slug' => 'fresh-cavendish-bananas',
                'sku' => 'FRT-BAN-001',
                'pricing_type' => 'weighted',
                'unit_of_measure' => 'kg',
                'step_weight_grams' => 500,
                'price' => 120.00,
                'stock' => 50.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'cat' => 'fruits-vegetables',
                'name' => 'Crisp Royal Gala Apples',
                'slug' => 'crisp-royal-gala-apples',
                'sku' => 'FRT-APP-002',
                'pricing_type' => 'weighted',
                'unit_of_measure' => 'kg',
                'step_weight_grams' => 500,
                'price' => 240.00,
                'stock' => 35.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'cat' => 'fruits-vegetables',
                'name' => 'Organic Fresh Baby Spinach (250g Pack)',
                'slug' => 'organic-baby-spinach-pack',
                'sku' => 'VEG-SPN-003',
                'pricing_type' => 'fixed_unit',
                'unit_of_measure' => 'pack',
                'step_weight_grams' => null,
                'price' => 95.00,
                'stock' => 20.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=600&q=80',
            ],

            // Dairy & Eggs (Fixed Units)
            [
                'cat' => 'dairy-eggs',
                'name' => 'Fresh Whole Pasteurized Milk (1 Liter)',
                'slug' => 'fresh-whole-milk-1l',
                'sku' => 'DAI-MLK-001',
                'pricing_type' => 'fixed_unit',
                'unit_of_measure' => 'bottle',
                'step_weight_grams' => null,
                'price' => 85.00,
                'stock' => 60.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'cat' => 'dairy-eggs',
                'name' => 'Farm Fresh Brown Eggs (Tray of 15)',
                'slug' => 'farm-fresh-brown-eggs-15',
                'sku' => 'DAI-EGG-002',
                'pricing_type' => 'fixed_unit',
                'unit_of_measure' => 'tray',
                'step_weight_grams' => null,
                'price' => 270.00,
                'stock' => 25.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'cat' => 'dairy-eggs',
                'name' => 'Artisan Aged Cheddar Cheese Block',
                'slug' => 'artisan-aged-cheddar-cheese',
                'sku' => 'DAI-CHS-003',
                'pricing_type' => 'weighted',
                'unit_of_measure' => 'kg',
                'step_weight_grams' => 200,
                'price' => 850.00,
                'stock' => 15.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1618160702438-9b02ab6515c9?auto=format&fit=crop&w=600&q=80',
            ],

            // Meat (Weighted)
            [
                'cat' => 'meat-poultry',
                'name' => 'Boneless Skinless Chicken Breast',
                'slug' => 'boneless-chicken-breast',
                'sku' => 'MET-CHK-001',
                'pricing_type' => 'weighted',
                'unit_of_measure' => 'kg',
                'step_weight_grams' => 500,
                'price' => 480.00,
                'stock' => 30.00,
                'is_perishable' => true,
                'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=600&q=80',
            ],

            // Pantry (Fixed Unit)
            [
                'cat' => 'pantry-beverages',
                'name' => 'Extra Virgin Olive Oil (Cold Pressed, 1L)',
                'slug' => 'extra-virgin-olive-oil-1l',
                'sku' => 'PAN-OIL-001',
                'pricing_type' => 'fixed_unit',
                'unit_of_measure' => 'bottle',
                'step_weight_grams' => null,
                'price' => 750.00,
                'stock' => 40.00,
                'is_perishable' => false,
                'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'cat' => 'pantry-beverages',
                'name' => 'Roasted Ethiopian Yirgacheffe Coffee Beans (500g)',
                'slug' => 'ethiopian-yirgacheffe-coffee-500g',
                'sku' => 'PAN-COF-002',
                'pricing_type' => 'fixed_unit',
                'unit_of_measure' => 'bag',
                'step_weight_grams' => null,
                'price' => 380.00,
                'stock' => 50.00,
                'is_perishable' => false,
                'image_url' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80',
            ],
        ];

        foreach ($productsData as $p) {
            $product = Product::firstOrCreate(
                ['sku' => $p['sku']],
                [
                    'category_id' => $categories[$p['cat']]->id,
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'pricing_type' => $p['pricing_type'],
                    'unit_of_measure' => $p['unit_of_measure'],
                    'step_weight_grams' => $p['step_weight_grams'],
                    'image_url' => $p['image_url'],
                    'is_perishable' => $p['is_perishable'],
                    'is_active' => true,
                ]
            );

            // Link to branch inventory
            BranchProduct::updateOrCreate(
                ['branch_id' => $branch->id, 'product_id' => $product->id],
                [
                    'price' => $p['price'],
                    'discount_price' => null,
                    'stock_quantity' => $p['stock'],
                    'is_in_stock' => true,
                ]
            );
        }

        // 6. Create a Sample Active Order demonstrating Live Tracking & Fulfillment
        $banana = Product::where('sku', 'FRT-BAN-001')->first();
        $milk = Product::where('sku', 'DAI-MLK-001')->first();
        $coffee = Product::where('sku', 'PAN-COF-002')->first();

        $subtotal = (120.00 * 1.5) + (85.00 * 2) + 380.00; // 180 + 170 + 380 = 730
        $deliveryFee = 50.00;
        $total = $subtotal + $deliveryFee;

        $order = Order::firstOrCreate(
            ['order_number' => 'ORD-20261007-DEMO1'],
            [
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'address_id' => $customerAddress->id,
                'delivery_address_snapshot' => [
                    'label' => $customerAddress->label,
                    'latitude' => $customerAddress->latitude,
                    'longitude' => $customerAddress->longitude,
                    'city' => $customerAddress->city,
                    'district_area' => $customerAddress->district_area,
                    'street_name' => $customerAddress->street_name,
                    'building_name_or_no' => $customerAddress->building_name_or_no,
                    'house_villa_no' => $customerAddress->house_villa_no,
                    'floor_number' => $customerAddress->floor_number,
                    'apartment_unit' => $customerAddress->apartment_unit,
                    'gate_code' => $customerAddress->gate_code,
                    'nearby_landmark' => $customerAddress->nearby_landmark,
                    'delivery_instructions' => $customerAddress->delivery_instructions,
                    'customer_phone' => $customer->phone_number,
                ],
                'status' => 'out_for_delivery',
                'delivery_type' => 'express_immediate',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'estimated_total' => $total,
                'final_total' => $total,
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'pending',
                'delivery_otp' => '482910', // Yango verification OTP
                'picker_id' => $picker->id,
                'special_notes' => 'Please deliver as quickly as possible. Thanks!',
            ]
        );

        // Order Items
        OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $banana->id],
            [
                'requested_quantity' => 1.50, // 1.5 kg
                'picked_quantity' => 1.52,   // Picker weighed 1.52 kg
                'unit_price' => 120.00,
                'final_item_total' => 182.40,
                'substitution_preference' => 'best_match',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $milk->id],
            [
                'requested_quantity' => 2.00,
                'picked_quantity' => 2.00,
                'unit_price' => 85.00,
                'final_item_total' => 170.00,
                'substitution_preference' => 'contact_customer',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $coffee->id],
            [
                'requested_quantity' => 1.00,
                'picked_quantity' => 1.00,
                'unit_price' => 380.00,
                'final_item_total' => 380.00,
                'substitution_preference' => 'do_not_substitute',
            ]
        );

        // Active Driver Trip for Live Tracking Demo
        $trip = DriverTrip::firstOrCreate(
            ['order_id' => $order->id],
            [
                'driver_id' => $driver->id,
                'status' => 'delivering',
                'started_at' => now()->subMinutes(8),
                'distance_km' => 3.20,
                'duration_minutes' => 12,
            ]
        );

        // Seed realistic in-transit GPS breadcrumb
        DriverLocation::firstOrCreate(
            ['trip_id' => $trip->id, 'created_at' => now()->subMinutes(1)],
            [
                'latitude' => 9.00680000,
                'longitude' => 38.77700000,
                'heading' => 315.0,
                'speed' => 28.5,
            ]
        );
    }
}
