<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Seed the application's roles and default user.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['Admin', 'Manager', 'Cashier'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $user = User::updateOrCreate(
            ['email' => 'tahmid.tf1@gmail.com'],
            [
                'name' => 'Tahmid',
                'password' => Hash::make('12345678'),
            ]
        );

        $user->syncRoles(['Admin']);
    }
}
