<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BorrowDetails;

class BorrowDetailsSeeder extends Seeder
{
    public function run()
    {
        BorrowDetails::create([
            [
                'borrow_details_id' => 164,
                'book_id' => 16,
                'borrow_id' => 484,
                'borrow_status' => 'pending',
                'date_return' => ''
            ],
            [
                'borrow_details_id' => 162,
                'book_id' => 15,
                'borrow_id' => 482,
                'borrow_status' => 'pending',
                'date_return' => ''
            ],
            [
                'borrow_details_id' => 163,
                'book_id' => 15,
                'borrow_id' => 483,
                'borrow_status' => 'returned',
                'date_return' => '2014-03-21 00:30:51'
            ]
        ]);
        
    }
}