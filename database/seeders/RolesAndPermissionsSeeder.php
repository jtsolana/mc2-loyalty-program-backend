<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'auth.register', 'display_name' => 'Register', 'group' => 'auth'],
            ['name' => 'points.view', 'display_name' => 'View Points', 'group' => 'points'],
            ['name' => 'points.earn', 'display_name' => 'Earn Points', 'group' => 'points'],
            ['name' => 'points.redeem', 'display_name' => 'Redeem Points', 'group' => 'points'],
            ['name' => 'points.adjust', 'display_name' => 'Adjust Points', 'group' => 'points'],
            ['name' => 'customers.view', 'display_name' => 'View Customers', 'group' => 'customers'],
            ['name' => 'customers.manage', 'display_name' => 'Manage Customers', 'group' => 'customers'],
            ['name' => 'point-rules.manage', 'display_name' => 'Manage Point Rules', 'group' => 'admin'],
            ['name' => 'reward-rules.manage', 'display_name' => 'Manage Reward Rules', 'group' => 'admin'],
            ['name' => 'promotions.manage', 'display_name' => 'Manage Promotions', 'group' => 'admin'],
            ['name' => 'roles.manage', 'display_name' => 'Manage Roles', 'group' => 'admin'],
        ];

        foreach ($permissions as $data) {
            Permission::firstOrCreate(['name' => $data['name']], $data);
        }

        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access',
                'permissions' => [
                    'points.view', 'points.earn', 'points.redeem', 'points.adjust',
                    'customers.view', 'customers.manage',
                    'point-rules.manage', 'reward-rules.manage', 'promotions.manage', 'roles.manage',
                ],
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Can process point transactions',
                'permissions' => [
                    'points.view', 'points.earn', 'points.redeem',
                    'customers.view',
                ],
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Loyalty program member',
                'permissions' => [
                    'auth.register', 'points.view',
                ],
            ],
        ];

        foreach ($roles as $data) {
            $role = Role::firstOrCreate(
                ['name' => $data['name']],
                ['display_name' => $data['display_name'], 'description' => $data['description']]
            );

            $permissionIds = Permission::whereIn('name', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
