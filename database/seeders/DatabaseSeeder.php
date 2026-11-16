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
        User::firstOrCreate(
            ['email' => 'supervisor@herbatech.co.id'],
            [
                'name' => 'Budi Santoso',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(ProductionMasterSeeder::class);
    }
}
