<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
            'password' => 'password',
        ]);

        Warehouse::query()->firstOrCreate([
            'code' => 'MAIN',
        ], [
            'name' => 'Main Warehouse',
            'address' => 'Demo address',
            'is_active' => true,
        ]);
    }
}
