<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => config('marketplace.admin.email')],
            [
                'name' => config('marketplace.admin.name'),
                'password' => config('marketplace.admin.password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([UserRole::Admin, UserRole::Buyer]);
    }
}
