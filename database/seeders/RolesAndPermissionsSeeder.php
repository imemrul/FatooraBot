<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Granular: each domain has view + manage
        $permissions = [
            // Users
            'view_users',
            'manage_users',

            // Inventory / Warehouse
            'view_inventory',
            'manage_inventory',

            // Invoices
            'view_invoices',
            'manage_invoices',

            // Customers
            'view_customers',
            'manage_customers',

            // Reports
            'view_reports',
            'manage_reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── owner: full access ──
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web'])
            ->syncPermissions($permissions);

        // ── accountant: invoices + customers + reports, view inventory ──
        Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web'])
            ->syncPermissions([
                'view_customers', 'manage_customers',
                'view_invoices', 'manage_invoices',
                'view_inventory',
                'view_reports', 'manage_reports',
            ]);

        // ── warehouse_manager: inventory + view invoices/customers/reports ──
        Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web'])
            ->syncPermissions([
                'view_inventory', 'manage_inventory',
                'view_invoices',
                'view_customers',
                'view_reports',
            ]);

        // ── salesman: customers + invoices + view inventory/reports ──
        Role::firstOrCreate(['name' => 'salesman', 'guard_name' => 'web'])
            ->syncPermissions([
                'view_customers', 'manage_customers',
                'view_invoices', 'manage_invoices',
                'view_inventory',
                'view_reports',
            ]);
    }
}
