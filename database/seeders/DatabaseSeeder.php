<?php

namespace Database\Seeders;

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
        // 1. Akun Admin Utama
        User::updateOrCreate(
            ['email' => 'admin@herbatech.co.id'],
            [
                'name' => 'Administrator Produksi',
                'role' => 'admin',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Team Leader
        User::updateOrCreate(
            ['email' => 'leader@herbatech.co.id'],
            [
                'name' => 'Wahyu (Team Leader)',
                'role' => 'teamleader',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Akun Supervisor (Role Admin)
        User::updateOrCreate(
            ['email' => 'supervisor@herbatech.co.id'],
            [
                'name' => 'Budi Santoso',
                'role' => 'admin',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(ProductionMasterSeeder::class);
    }
}
