<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // User::factory(10)->create();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
            'zip_code' => '12345',
            'city' => 'Admin City',
            'state' => 'Admin State',
        ]);
        $admin->assignRole('admin');

        $seller = User::create([
            'name' => 'Seller User',
            'email' => 'seller@mail.com',
            'password' => Hash::make('password'),
            'zip_code' => '54321',
            'city' => 'Seller City',
            'state' => 'Seller State',
            ]);
        $seller->assignRole('seller');

        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@mail.com',
            'password' => Hash::make('password'),
            'zip_code' => '67890',
            'city' => 'Customer City',
            'state' => 'Customer State',
        ]);
        $customer->assignRole('customer');
    }
}
