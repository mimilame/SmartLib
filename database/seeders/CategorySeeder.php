<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Category::create([
        [
            ['category_id' => 1, 'classname' => 'General Reference'],
            ['category_id' => 2, 'classname' => 'English and Literature'],
            ['category_id' => 3, 'classname' => 'Mathematics'],
            ['category_id' => 4, 'classname' => 'Science and Technology'],
            ['category_id' => 5, 'classname' => 'Humanities and Philosophy'],
            ['category_id' => 6, 'classname' => 'Social Sciences'],
            ['category_id' => 7, 'classname' => 'Medical and Health Sciences'],
            ['category_id' => 8, 'classname' => 'Law and Legal Studies'],
            ['category_id' => 9, 'classname' => 'Business and Management'],
            ['category_id' => 10, 'classname' => 'Arts and Design'],
            ['category_id' => 11, 'classname' => 'Filipiniana'],
            ['category_id' => 12, 'classname' => 'Engineering and Applied Sciences'],
            ['category_id' => 13, 'classname' => 'Language and Linguistics'],
            ['category_id' => 14, 'classname' => 'Theses and Dissertations'],
            ['category_id' => 15, 'classname' => 'Periodicals'],
            ['category_id' => 16, 'classname' => 'Newspapers'],
            ['category_id' => 17, 'classname' => 'Archives and Special Collections'],
            ['category_id' => 18, 'classname' => 'Digital Resources'],
            ['category_id' => 19, 'classname' => 'History and Archaeology'],
            ['category_id' => 20, 'classname' => 'Education and Teaching Resources'],
        ]
        ]);
        
    }
}