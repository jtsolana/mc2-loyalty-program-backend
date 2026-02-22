<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'mcadmin'],
            [
                'name' => 'MC Admin',
                'email' => 'jtoclarit@gmail.com',
                // TODO: Change this password before going to production!
                'password' => Hash::make('admin'),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole && ! $admin->roles()->where('name', 'admin')->exists()) {
            $admin->roles()->attach($adminRole);
        }

        $this->command->warn('⚠  Admin user created with a default password. Remember to change it before going live!');
        $this->command->line('   Username : mcadmin');
        $this->command->line('   Password : admin  ← CHANGE THIS');
    }
}
