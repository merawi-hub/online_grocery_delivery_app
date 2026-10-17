<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supermarket;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\BranchProduct;
use App\Models\DeliveryZone;
use App\Models\StoreMember;
use Illuminate\Support\Facades\Hash;

class MultiStoreMarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data to allow re-seeding
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('store_members')->truncate();
        \DB::table('delivery_zones')->truncate();
        \DB::table('branch_products')->truncate();
        \DB::table('products')->truncate();
        \DB::table('categories')->truncate();
        \DB::table('branches')->truncate();
        \DB::table('supermarkets')->truncate();
        \DB::table('users')->where('email', 'like', '%@grocery.com')->orWhere('email', 'like', '%@example.com')->delete();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@grocery.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone_number' => '+1234567890',
            'approval_status' => 'approved',
        ]);

        // Create 5 Supermarkets with Branches
        $supermarkets = [
            ['name' => 'Fresh Market', 'slug' => 'fresh-market', 'description' => 'Your neighborhood fresh grocery store', 'logo_url' => null],
            ['name' => 'Value Grocers', 'slug' => 'value-grocers', 'description' => 'Quality products at great prices', 'logo_url' => null],
            ['name' => 'Organic Hub', 'slug' => 'organic-hub', 'description' => 'Premium organic and natural products', 'logo_url' => null],
            ['name' => 'QuickStop Mart', 'slug' => 'quickstop-mart', 'description' => 'Fast and convenient grocery shopping', 'logo_url' => null],
            ['name' => 'Family Foods', 'slug' => 'family-foods', 'description' => 'Serving families for over 50 years', 'logo_url' => null],
        ];

        $branches = [];
        foreach ($supermarkets as $index => $supermarketData) {
            $supermarket = Supermarket::create($supermarketData);

            // Create 2-3 branches per supermarket
            $branchCount = $index % 2 == 0 ? 2 : 3;
            for ($i = 1; $i <= $branchCount; $i++) {
                $branch = Branch::create([
                    'supermarket_id' => $supermarket->id,
                    'name' => $supermarket->name . ' - Branch ' . $i,
                    'address' => '123 Main St, District ' . ($index + 1) . ', City',
                    'latitude' => 40.7128 + ($index * 0.05) + ($i * 0.01),
                    'longitude' => -74.0060 + ($index * 0.05) + ($i * 0.01),
                    'contact_phone' => '+123456789' . $index . $i,
                    'email' => 'branch' . ($index + 1) . $i . '@' . strtolower(str_replace(' ', '', $supermarket->name)) . '.com',
                    'is_open' => true,
                    'delivery_radius_km' => 5.0,
                    'minimum_order_amount' => 10.00,
                    'average_preparation_minutes' => 30,
                    'commission_rate' => 0.15,
                    'opening_time' => '08:00:00',
                    'closing_time' => '22:00:00',
                    'operating_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                ]);

                $branches[] = $branch;

                // Create delivery zone for this branch
                DeliveryZone::create([
                    'store_id' => $branch->id,
                    'name' => 'Zone ' . chr(64 + $i),
                    'description' => 'Delivery zone ' . chr(64 + $i) . ' for ' . $branch->name,
                    'type' => 'radius',
                    'radius_km' => 5.0,
                    'polygon_coordinates' => null,
                    'delivery_fee' => 2.99 + ($i * 0.50),
                    'minimum_order' => 10.00,
                    'estimated_minutes' => 30,
                    'is_active' => true,
                ]);

                // Create manager for this branch
                $manager = User::create([
                    'name' => 'Manager ' . $supermarket->name . ' B' . $i,
                    'email' => 'manager' . ($index + 1) . $i . '@grocery.com',
                    'password' => Hash::make('password'),
                    'role' => 'store_manager',
                    'phone_number' => '+123456789' . $index . $i . '00',
                    'branch_id' => $branch->id,
                    'approval_status' => 'approved',
                ]);

                // Add manager to store_members
                StoreMember::create([
                    'user_id' => $manager->id,
                    'store_id' => $branch->id,
                    'role' => 'manager',
                    'assigned_by' => $admin->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);

                // Create picker for this branch
                $picker = User::create([
                    'name' => 'Picker ' . $supermarket->name . ' B' . $i,
                    'email' => 'picker' . ($index + 1) . $i . '@grocery.com',
                    'password' => Hash::make('password'),
                    'role' => 'picker',
                    'phone_number' => '+123456789' . $index . $i . '11',
                    'branch_id' => $branch->id,
                ]);

                StoreMember::create([
                    'user_id' => $picker->id,
                    'store_id' => $branch->id,
                    'role' => 'inventory_staff',
                    'assigned_by' => $manager->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);
            }
        }

        // Create Drivers (independent, not branch-specific)
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => 'Driver ' . $i,
                'email' => 'driver' . $i . '@grocery.com',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'phone_number' => '+1234567' . str_pad($i, 3, '0', STR_PAD_LEFT),
            ]);
        }

        // Create Customers
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => 'Customer ' . $i,
                'email' => 'customer' . $i . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'phone_number' => '+1234567' . str_pad($i + 100, 3, '0', STR_PAD_LEFT),
            ]);
        }

        // Create Categories
        $categories = [
            ['name' => 'Fruits & Vegetables', 'slug' => 'fruits-vegetables'],
            ['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs'],
            ['name' => 'Meat & Seafood', 'slug' => 'meat-seafood'],
            ['name' => 'Bakery', 'slug' => 'bakery'],
            ['name' => 'Pantry', 'slug' => 'pantry'],
            ['name' => 'Beverages', 'slug' => 'beverages'],
            ['name' => 'Snacks', 'slug' => 'snacks'],
            ['name' => 'Frozen Foods', 'slug' => 'frozen-foods'],
            ['name' => 'Household', 'slug' => 'household'],
            ['name' => 'Personal Care', 'slug' => 'personal-care'],
        ];

        $categoryModels = [];
        foreach ($categories as $categoryData) {
            $categoryModels[] = Category::create($categoryData);
        }

        // Products and inventory are now managed dynamically by Store Managers via the Manager Portal (/admin123/inventory).
        // No hardcoded or dummy products are pre-seeded so the catalog only displays genuine items added by managers.

        $this->command->info('Multi-store marketplace seeded successfully!');
        $this->command->info('- 5 Supermarkets with 12 total branches');
        $this->command->info('- 10 Core Categories ready for Manager inventory');
        $this->command->info('- 12 Managers, 12 Pickers, 5 Drivers, 10 Customers, 1 Admin');
        $this->command->info('- 12 Managers, 12 Pickers, 5 Drivers, 10 Customers, 1 Admin');
        $this->command->info('Login credentials: email/password for any user');
    }
}
