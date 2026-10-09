<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SpecificUsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. General Manager (4th Stakeholder): name: admin, password: admin123, no need of mobile no
        $admin = User::where('name', 'admin')->orWhere('email', 'admin@grocery.test')->first();
        if ($admin) {
            $admin->update([
                'name' => 'admin',
                'first_name' => 'General',
                'last_name' => 'Manager',
                'email' => 'admin@grocery.test',
                'phone_number' => null,
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'approval_status' => 'approved',
                'is_active' => true,
            ]);
        } else {
            User::create([
                'name' => 'admin',
                'first_name' => 'General',
                'last_name' => 'Manager',
                'email' => 'admin@grocery.test',
                'phone_number' => null,
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'approval_status' => 'approved',
                'is_active' => true,
            ]);
        }

        // 2. Default Customer: Name: User1, Password: password123, phone: 0969644384
        $user1 = User::where('phone_number', '0969644384')->orWhere('name', 'User1')->first();
        if ($user1) {
            $user1->update([
                'name' => 'User1',
                'first_name' => 'User',
                'last_name' => '1',
                'phone_number' => '0969644384',
                'email' => 'user1@freshmart.et',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'approval_status' => 'approved',
                'is_active' => true,
            ]);
        } else {
            User::create([
                'name' => 'User1',
                'first_name' => 'User',
                'last_name' => '1',
                'phone_number' => '0969644384',
                'email' => 'user1@freshmart.et',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'approval_status' => 'approved',
                'is_active' => true,
            ]);
        }

        // 3. Existing Stock Manager: Sara Manager
        $manager = User::where('email', 'manager@grocery.test')->orWhere('phone_number', '0911000002')->first();
        if ($manager) {
            $manager->update([
                'name' => 'Sara Manager',
                'first_name' => 'Sara',
                'last_name' => 'Manager',
                'phone_number' => '0911000002',
                'email' => 'manager@grocery.test',
                'password' => Hash::make('password123'),
                'role' => 'store_manager',
                'approval_status' => 'approved',
                'is_active' => true,
            ]);
        } else {
            User::create([
                'name' => 'Sara Manager',
                'first_name' => 'Sara',
                'last_name' => 'Manager',
                'phone_number' => '0911000002',
                'email' => 'manager@grocery.test',
                'password' => Hash::make('password123'),
                'role' => 'store_manager',
                'approval_status' => 'approved',
                'is_active' => true,
            ]);
        }

        // 4. Pending Stock Manager for approval demo
        $managerPending = User::where('phone_number', '0922334455')->first();
        if (!$managerPending) {
            User::create([
                'name' => 'Kidus Alemu',
                'first_name' => 'Kidus',
                'last_name' => 'Alemu',
                'phone_number' => '0922334455',
                'email' => 'kidus.stock@grocery.test',
                'password' => Hash::make('password123'),
                'role' => 'store_manager',
                'approval_status' => 'pending',
                'is_active' => true,
            ]);
        }

        // 5. Approved Deliverer: Yonas Driver
        $driver = User::where('email', 'driver@grocery.test')->orWhere('phone_number', '0911000004')->first();
        if ($driver) {
            $driver->update([
                'name' => 'Yonas Driver',
                'first_name' => 'Yonas',
                'last_name' => 'Driver',
                'phone_number' => '0911000004',
                'email' => 'driver@grocery.test',
                'password' => Hash::make('password123'),
                'role' => 'driver',
                'approval_status' => 'approved',
                'vehicle_type' => 'Motorbike',
                'is_active' => true,
            ]);
        } else {
            User::create([
                'name' => 'Yonas Driver',
                'first_name' => 'Yonas',
                'last_name' => 'Driver',
                'phone_number' => '0911000004',
                'email' => 'driver@grocery.test',
                'password' => Hash::make('password123'),
                'role' => 'driver',
                'approval_status' => 'approved',
                'vehicle_type' => 'Motorbike',
                'is_active' => true,
            ]);
        }

        // 6. Pending Deliverer for approval demo: Tariku Bekele
        $driverPending = User::where('phone_number', '0933445566')->first();
        if (!$driverPending) {
            User::create([
                'name' => 'Tariku Bekele',
                'first_name' => 'Tariku',
                'last_name' => 'Bekele',
                'phone_number' => '0933445566',
                'email' => 'tariku.driver@grocery.test',
                'password' => Hash::make('password123'),
                'role' => 'driver',
                'approval_status' => 'pending',
                'vehicle_type' => 'Bicycle',
                'is_active' => true,
            ]);
        }
    }
}
