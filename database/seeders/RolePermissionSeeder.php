<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage products',
            'manage orders',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        
        // Admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Manager
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'view dashboard',
            'manage products',
            'manage orders',
            'view reports',
        ]);

        // User (e.g., Sales Representative)
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo([
            'view dashboard',
            'manage orders',
        ]);
        
        // Assign Admin role to the default Admin user
        $adminUser = \App\Models\User::where('email', 'admin@citropak.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }
    }
}
