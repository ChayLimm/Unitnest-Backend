<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('role_name', 'Admin')->first();

        if ($adminRole) {
            User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'admin@lomnov.com',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]);
        }
    }
}
