<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@acs.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            RolesAndPermissionsSeeder::class,
            DefaultCustomerServiceSeeder::class,
            ConfigurationTemplatesSeeder::class,
            RouterManufacturersSeeder::class,
            RouterProductsSeeder::class,
            CpeProductsSeeder::class,
            AlertRulesSeeder::class,
        ]);
    }
}
