<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Using the correct column names from your original schema
        User::create([
            'user_id' => 2,
            'username' => 'admin',
            'email' => 'admin@wmsu.edu.ph',
            'password' => Hash::make('admin'), 
            'firstname' => 'Admin', 
            'lastname' => 'User',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ],
        [
            'user_id' => 3,
            'username' => 'librarian',
            'email' => 'librarian@wmsu.edu.ph',
            'password' => Hash::make('password'), 
            'firstname' => 'Library',
            'lastname' => 'Manager',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
    }
}