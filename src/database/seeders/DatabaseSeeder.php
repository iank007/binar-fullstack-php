<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin User',
                'email'    => 'admin@binar.co',
                'password' => Hash::make('P@ssword123!'),
                'role'     => UserRole::Administrator,
                'active'   => true,
            ],
            [
                'name'     => 'Manager User',
                'email'    => 'manager@binar.co',
                'password' => Hash::make('P@ssword123!'),
                'role'     => UserRole::Manager,
                'active'   => true,
            ],
            [
                'name'     => 'Regular User',
                'email'    => 'user@binar.co',
                'password' => Hash::make('P@ssword123!'),
                'role'     => UserRole::User,
                'active'   => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);

            // Create a few sample orders per user
            Order::factory()->count(3)->create(['user_id' => $user->id]);

            $token = $user->createToken('seed-token')->plainTextToken;

            $this->command->info("Created {$user->role->value}: {$user->email} | Token: {$token}");
        }
    }
}
