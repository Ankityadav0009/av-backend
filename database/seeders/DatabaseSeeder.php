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
        $this->call([
            RoleSeeder::class,
            MenuSeeder::class,
        ]);

        // Plain password - User model has 'hashed' cast, so it will auto-hash
        User::updateOrCreate(
            ['email' => 'admin@avguest.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@avguest.com',
                'password' => 'password123',
                'email_verified_at' => now(),
                'role_id' => \App\Models\Role::where('code', 'admin')->value('id'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo@hotel.com'],
            [
                'name' => 'Demo User',
                'email' => 'demo@hotel.com',
                'password' => 'Hotel@123',
                'email_verified_at' => now(),
                'role_id' => \App\Models\Role::where('code', 'receptionist')->value('id'),
            ]
        );
    }
}
