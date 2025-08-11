<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'manage products',
            'manage orders',
            'manage users',
            'view seller dashboard',
            'view customer dashboard',
            'manage own products',
            'manage own orders',
            'view products',
            'place orders',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $sellerRole = Role::create(['name' => 'seller']);
        $sellerRole->givePermissionTo([
            'view dashboard',
            'view seller dashboard',
            'manage own products',
            'manage own orders',
            'view products',
        ]);

        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view dashboard',
            'view customer dashboard',
            'view products',
            'place orders',
            'manage own orders',
        ]);

        // Create admin user
        
    }
}
