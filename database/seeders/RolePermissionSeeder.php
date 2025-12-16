<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Modules and their CRUD permissions
     */
    protected array $modules = [
        'users',
        'roles',
        'orders',
        'products',
        'stock',
        'invoices',
        'reports',
        'customers',
        'customer_attributes',
        'order_bookers',
        'vans',
    ];

    /**
     * CRUD actions for each module
     */
    protected array $actions = [
        'view',
        'create',
        'edit',
        'delete',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions automatically for all modules
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();
        
        // Assign roles to existing users
        $this->assignRolesToUsers();
    }

    /**
     * Auto-generate CRUD permissions for all modules
     */
    protected function createPermissions(): void
    {
        // Dashboard permission
        Permission::firstOrCreate(['name' => 'view dashboard', 'guard_name' => 'web']);

        // Generate CRUD permissions for each module
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    /**
     * Create roles with their permissions
     */
    protected function createRoles(): void
    {
        // Superadmin - All permissions
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadminRole->syncPermissions(Permission::all());

        // Admin - All permissions except role management
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(
            Permission::whereNotIn('name', [
                'roles.create',
                'roles.edit',
                'roles.delete',
            ])->get()
        );

        // Manager - Products, Orders, Stock, Reports
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->syncPermissions([
            'view dashboard',
            'products.view',
            'products.create',
            'products.edit',
            'orders.view',
            'orders.create',
            'orders.edit',
            'stock.view',
            'stock.create',
            'stock.edit',
            'invoices.view',
            'invoices.create',
            'reports.view',
        ]);

        // User (Sales Representative) - Orders and view access
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'view dashboard',
            'orders.view',
            'orders.create',
            'products.view',
            'stock.view',
            'invoices.view',
        ]);

        // Customer - Limited access
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customerRole->syncPermissions([
            'view dashboard',
            'orders.view',
            'orders.create',
            'invoices.view',
        ]);
    }

    /**
     * Assign roles to existing users
     */
    protected function assignRolesToUsers(): void
    {
        // Assign superadmin role to admin user
        $admin = \App\Models\User::where('email', 'admin@citropak.com')->first();
        if ($admin) {
            $admin->syncRoles(['superadmin']);
        }
    }
}
