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

        // Create Products
        $productBaseData = [
            ['name' => 'Organic Bananas', 'slug' => 'organic-bananas', 'category_id' => 1],
            ['name' => 'Strawberries', 'slug' => 'strawberries', 'category_id' => 1],
            ['name' => 'Avocados', 'slug' => 'avocados', 'category_id' => 1],
            ['name' => 'Baby Spinach', 'slug' => 'baby-spinach', 'category_id' => 1],
            ['name' => 'Cherry Tomatoes', 'slug' => 'cherry-tomatoes', 'category_id' => 1],
            ['name' => 'Carrots', 'slug' => 'carrots', 'category_id' => 1],
            ['name' => 'Broccoli', 'slug' => 'broccoli', 'category_id' => 1],
            ['name' => 'Red Bell Peppers', 'slug' => 'red-bell-peppers', 'category_id' => 1],
            
            ['name' => 'Whole Milk', 'slug' => 'whole-milk', 'category_id' => 2],
            ['name' => 'Greek Yogurt', 'slug' => 'greek-yogurt', 'category_id' => 2],
            ['name' => 'Large Eggs', 'slug' => 'large-eggs', 'category_id' => 2],
            ['name' => 'Cheddar Cheese', 'slug' => 'cheddar-cheese', 'category_id' => 2],
            ['name' => 'Butter', 'slug' => 'butter', 'category_id' => 2],
            ['name' => 'Cream Cheese', 'slug' => 'cream-cheese', 'category_id' => 2],
            
            ['name' => 'Chicken Breast', 'slug' => 'chicken-breast', 'category_id' => 3],
            ['name' => 'Ground Beef', 'slug' => 'ground-beef', 'category_id' => 3],
            ['name' => 'Atlantic Salmon', 'slug' => 'atlantic-salmon', 'category_id' => 3],
            ['name' => 'Pork Chops', 'slug' => 'pork-chops', 'category_id' => 3],
            
            ['name' => 'Whole Wheat Bread', 'slug' => 'whole-wheat-bread', 'category_id' => 4],
            ['name' => 'Croissants', 'slug' => 'croissants', 'category_id' => 4],
            ['name' => 'Bagels', 'slug' => 'bagels', 'category_id' => 4],
            ['name' => 'Sourdough Bread', 'slug' => 'sourdough-bread', 'category_id' => 4],
            
            ['name' => 'Pasta Penne', 'slug' => 'pasta-penne', 'category_id' => 5],
            ['name' => 'White Rice', 'slug' => 'white-rice', 'category_id' => 5],
            ['name' => 'Canned Tomatoes', 'slug' => 'canned-tomatoes', 'category_id' => 5],
            ['name' => 'Black Beans', 'slug' => 'black-beans', 'category_id' => 5],
            ['name' => 'Olive Oil', 'slug' => 'olive-oil', 'category_id' => 5],
            ['name' => 'Peanut Butter', 'slug' => 'peanut-butter', 'category_id' => 5],
            
            ['name' => 'Orange Juice', 'slug' => 'orange-juice', 'category_id' => 6],
            ['name' => 'Bottled Water', 'slug' => 'bottled-water', 'category_id' => 6],
            ['name' => 'Coffee Beans', 'slug' => 'coffee-beans', 'category_id' => 6],
            ['name' => 'Green Tea', 'slug' => 'green-tea', 'category_id' => 6],
            ['name' => 'Cola', 'slug' => 'cola', 'category_id' => 6],
            
            ['name' => 'Potato Chips', 'slug' => 'potato-chips', 'category_id' => 7],
            ['name' => 'Granola Bars', 'slug' => 'granola-bars', 'category_id' => 7],
            ['name' => 'Chocolate Chip Cookies', 'slug' => 'chocolate-chip-cookies', 'category_id' => 7],
            ['name' => 'Pretzels', 'slug' => 'pretzels', 'category_id' => 7],
            ['name' => 'Trail Mix', 'slug' => 'trail-mix', 'category_id' => 7],
            
            ['name' => 'Frozen Pizza', 'slug' => 'frozen-pizza', 'category_id' => 8],
            ['name' => 'Ice Cream', 'slug' => 'ice-cream', 'category_id' => 8],
            ['name' => 'Frozen Vegetables', 'slug' => 'frozen-vegetables', 'category_id' => 8],
            ['name' => 'Frozen Waffles', 'slug' => 'frozen-waffles', 'category_id' => 8],
            ['name' => 'Fish Sticks', 'slug' => 'fish-sticks', 'category_id' => 8],
            
            ['name' => 'Paper Towels', 'slug' => 'paper-towels', 'category_id' => 9],
            ['name' => 'Toilet Paper', 'slug' => 'toilet-paper', 'category_id' => 9],
            ['name' => 'Dish Soap', 'slug' => 'dish-soap', 'category_id' => 9],
            ['name' => 'Laundry Detergent', 'slug' => 'laundry-detergent', 'category_id' => 9],
            ['name' => 'All-Purpose Cleaner', 'slug' => 'all-purpose-cleaner', 'category_id' => 9],
            
            ['name' => 'Shampoo', 'slug' => 'shampoo', 'category_id' => 10],
            ['name' => 'Toothpaste', 'slug' => 'toothpaste', 'category_id' => 10],
            ['name' => 'Deodorant', 'slug' => 'deodorant', 'category_id' => 10],
            ['name' => 'Hand Soap', 'slug' => 'hand-soap', 'category_id' => 10],
            ['name' => 'Body Lotion', 'slug' => 'body-lotion', 'category_id' => 10],
        ];

        $products = [];
        foreach ($productBaseData as $index => $data) {
            $products[] = array_merge($data, [
                'sku' => 'SKU-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'barcode' => '123456789' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'description' => 'Quality ' . strtolower($data['name']),
                'unit_of_measure' => 'each',
            ]);
        }

        $productModels = [];
        foreach ($products as $productData) {
            $productModels[] = Product::create($productData);
        }

        // Assign products to branches with varying availability and prices
        $basePrice = [1.99, 4.99, 3.99, 3.49, 2.99, 1.79, 2.49, 4.99, 4.49, 5.99, 3.99, 6.99, 4.99, 3.49, 5.99, 6.49, 12.99, 4.99, 3.49, 4.99, 3.99, 5.49, 1.99, 8.99, 1.49, 1.29, 12.99, 4.99, 5.99, 6.99, 9.99, 4.49, 5.99, 3.99, 4.49, 3.49, 2.99, 6.99, 6.99, 5.99, 2.99, 3.49, 7.99, 12.99, 14.99, 3.99, 11.99, 4.49, 6.99, 4.49, 5.99, 3.49, 7.99];
        
        foreach ($branches as $branch) {
            // Each branch gets 70-90% of all products
            $productsToAssign = fake()->numberBetween(40, 50);
            $selectedProducts = fake()->randomElements($productModels, $productsToAssign);

            foreach ($selectedProducts as $index => $product) {
                // Vary price slightly by branch (±10%)
                $productIndex = array_search($product, $productModels);
                $productBasePrice = $basePrice[$productIndex] ?? 5.99;
                $priceVariation = fake()->randomFloat(2, -0.10, 0.10);
                $branchPrice = round($productBasePrice * (1 + $priceVariation), 2);

                BranchProduct::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'stock_quantity' => fake()->numberBetween(10, 200),
                    'is_in_stock' => true,
                    'price' => $branchPrice,
                ]);
            }
        }

        $this->command->info('Multi-store marketplace seeded successfully!');
        $this->command->info('- 5 Supermarkets with 12 total branches');
        $this->command->info('- 53 Products across 10 categories');
        $this->command->info('- 12 Managers, 12 Pickers, 5 Drivers, 10 Customers, 1 Admin');
        $this->command->info('Login credentials: email/password for any user');
    }
}
