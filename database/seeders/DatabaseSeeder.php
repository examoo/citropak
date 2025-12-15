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
        // Create Admin user
        User::updateOrCreate(
            ['email' => 'admin@citropak.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('12345678'),
            ]
        );

        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
