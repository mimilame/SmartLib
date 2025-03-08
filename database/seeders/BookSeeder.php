<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books =
        [
            [
                'book_id' => 15,  // Kept as is
                'book_title' => 'Natural Resources',
                'category_id' => 8,
                'author' => 'Robin Kerrod',
                'book_copies' => 15,
                'book_pub' => 'Marshall Cavendish Corporation',
                'publisher_name' => 'Marshall',
                'isbn' => '1-85435-628-3',  // Consistent with LostBook reference
                'copyright_year' => 1997,
                'date_receive' => '',
                'date_added' => '2013-12-11 06:34:27',
                'status' => 'New'
            ],
            [
                'book_id' => 16,  // Kept as is
                'book_title' => 'Encyclopedia Americana',
                'category_id' => 5,
                'author' => 'Grolier',
                'book_copies' => 20,
                'book_pub' => 'Connecticut',
                'publisher_name' => 'Grolier Incorporation',
                'isbn' => '0-7172-0119-8',  // Consistent with LostBook reference
                'copyright_year' => 1988,
                'date_receive' => '',
                'date_added' => '2013-12-11 06:36:23',
                'status' => 'Archive'
            ]
        ];
        Book::insert($books);
    }
}
