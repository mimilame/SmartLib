<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Make sure you're using your custom seeders instead of default ones
        $this->call([
            CategorySeeder::class,
            TypeSeeder::class,
            UserSeeder::class,
            MemberSeeder::class,
            BookSeeder::class,
            BorrowSeeder::class,
            BorrowDetailsSeeder::class,
            LostBookSeeder::class,
        ]);
        
        // Remove this line if it exists - it's using the default User factory
        // \App\Models\User::factory(10)->create();
    }
}
