<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default
        User::updateOrCreate(
            ['email' => 'admin@simpus.test'],
            [
                'name' => 'Admin SIMPUS',
                'password' => Hash::make('123456'),
                'role' => 'admin',
            ]
        );

        // Member default
        User::updateOrCreate(
            ['email' => 'member@simpus.test'],
            [
                'name' => 'Member SIMPUS',
                'password' => Hash::make('123456'),
                'role' => 'member',
            ]
        );
    }
}
