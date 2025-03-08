<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Borrow;

class BorrowSeeder extends Seeder
{
    public function run()
    {
        $borrows = [
            [
                'borrow_id' => 484,
                'member_id' => 55,
                'date_borrow' => '2014-03-20 23:50:27',
                'due_date' => '21/03/2014', 
                'updated_at' => now(),
            ],
            [
                'borrow_id' => 483,
                'member_id' => 55,
                'date_borrow' => '2014-03-20 23:49:34',
                'due_date' => '21/03/2014',
                'updated_at' => now(),
            ],
            [
                'borrow_id' => 482,
                'member_id' => 52,
                'date_borrow' => '2014-03-20 23:38:22',
                'due_date' => '03/01/2014',
                'updated_at' => now(),
            ]
        ];
        Borrow::insert($borrows);
    }
}