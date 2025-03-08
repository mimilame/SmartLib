<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\LostBook;
use Illuminate\Database\Seeder;

class LostBookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Since there's no sample data for lost books in the original SQL dump,
        // we'll just create a few examples
        LostBook::factory(5)->create();
    }
}