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
        // All seeded users have password: "password"

        User::factory()->superAdmin()->create([
            'name'        => 'Super Admin',
            'email'       => 'superadmin@xclusive.test',
            'country'     => 'Netherlands',
            'platform'    => 'steam',
            'platform_id' => 'xcl_superadmin',
            'team'        => 'XCL Staff',
        ]);

        User::factory()->admin()->create([
            'name'        => 'Admin',
            'email'       => 'admin@xclusive.test',
            'country'     => 'Netherlands',
            'platform'    => 'steam',
            'platform_id' => 'xcl_admin',
            'team'        => 'XCL Staff',
        ]);

        User::factory()->manager()->create([
            'name'        => 'Manager',
            'email'       => 'manager@xclusive.test',
            'country'     => 'Netherlands',
            'platform'    => 'ps5',
            'platform_id' => 'xcl_manager',
            'team'        => 'XCL Staff',
        ]);

        User::factory()->create([
            'name'        => 'Driver',
            'email'       => 'driver@xclusive.test',
            'country'     => 'Netherlands',
            'platform'    => 'xbox',
            'platform_id' => 'xcl_driver',
            'team'        => null,
        ]);

    }
}
