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
            'zip_code' => '75000',
            'city' => 'Paris',
            'state' => 'Ile-de-France',
            'country' => 'France',
        ]);
        $admin->assignRole('admin');

        $seller = User::create([
            'name' => 'Seller User',
            'email' => 'seller@mail.com',
            'password' => Hash::make('password'),
            'zip_code' => '74000',
            'city' => 'Annecy',
            'state' => 'Auvergne-Rhône-Alpes',
            'country' => 'France',
            ]);
        $seller->assignRole('seller');

        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@mail.com',
            'password' => Hash::make('password'),
            'zip_code' => '69000',
            'city' => 'Lyon',
            'state' => 'Auvergne-Rhône-Alpes',
            'country' => 'France',
        ]);
        $customer->assignRole('customer');

        // create a second seller
        $seller2 = User::create([
            'name' => 'Second Seller', 
            'email' => 'seller2@mail.com',
            'password' => Hash::make('password'),
            'zip_code' => '13000',
            'city' => 'Marseille',
            'state' => 'Provence-Alpes-Côte d\'Azur',
            'country' => 'France',
        ]);
        $seller2->assignRole('seller');

        // create a first product for the first seller
        \App\Models\Product::create([
            'user_id' => $seller->id,
            'name' => 'Sample Product 1',
            'description' => 'This is a sample product description.',
            'price' => 10.00,
            'stock_quantity' => 100,
            'category' => 'Sample Category',
        ]);
        // create a first product for the second seller
        \App\Models\Product::create([
            'user_id' => $seller2->id,
            'name' => 'Sample Product 2',
            'description' => 'This is another sample product description.',
            'price' => 20.00,
            'stock_quantity' => 50,
            'category' => 'Another Category',
        ]);
    }
}
