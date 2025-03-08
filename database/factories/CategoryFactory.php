<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $categories = [
            'General Reference',
            'English and Literature',
            'Mathematics',
            'Science and Technology',
            'Humanities and Philosophy',
            'Social Sciences',
            'Medical and Health Sciences',
            'Law and Legal Studies',
            'Business and Management',
            'Arts and Design',
            'Filipiniana',
            'Engineering and Applied Sciences',
            'Language and Linguistics',
            'Theses and Dissertations',
            'Periodicals',
            'Newspapers',
            'Archives and Special Collections',
            'Digital Resources',
            'History and Archaeology',
            'Education and Teaching Resources'
        ];
        

        return [
            'classname' => $this->faker->unique()->randomElement($categories),
        ];
    }
}
