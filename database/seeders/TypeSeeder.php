<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    public function run()
    {
        Type::create([
        [
            ['id' => 1, 'borrowertype' => 'Student'],
            ['id' => 2, 'borrowertype' => 'Teacher'],
            ['id' => 3, 'borrowertype' => 'Staff'],
            ['id' => 4, 'borrowertype' => 'Alumni'],
            ['id' => 5, 'borrowertype' => 'Employee'],
            ['id' => 6, 'borrowertype' => 'Non-Teaching'],
            ['id' => 7, 'borrowertype' => 'Researcher'],
            ['id' => 8, 'borrowertype' => 'Guest'],
            ['id' => 9, 'borrowertype' => 'Visiting Scholar'],
            ['id' => 10, 'borrowertype' => 'Contruction'],
        ]
        ]);

    }
}