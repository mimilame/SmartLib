<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 50 random members
        Member::factory()->count(50)->create();
        
        // Create specific members for testing
        Member::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'gender' => 'Male',
            'address' => 'University Campus',
            'contact' => '123-456-7890',
            'type' => 'Staff',
            'designation' => 'Administrative',
            'status' => 'Active',
            'membership_date' => now()->subYears(2),
            'borrowed_books_count' => 0,
            'expiry_date' => now()->addYears(5),
            'email' => 'admin@wmsu.edu.ph',
            'id_number' => 'ADMIN-001',
            'department' => 'Library'
        ]);

    }
}
