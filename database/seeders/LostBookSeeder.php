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
        $lostBooks = [
            [
                'book_id' => 15,  
                'isbn' => '1-85435-628-3', 
                'Member_No' => 39, 
                'date_lost' => '2023-05-10', 
            ],
            [
                'book_id' => 16, 
                'isbn' => '0-7172-0119-8', 
                'Member_No' => 40, 
                'date_lost' => '2023-06-12', 
            ],
        ];

        LostBook::insert($lostBooks); 
    }
}