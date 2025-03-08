<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'book_title' => $this->faker->sentence,
            'author' => $this->faker->name,
            'category_id' => \App\Models\Category::factory(),
            'book_copies' => $this->faker->randomDigitNotZero,
            'book_pub' => $this->faker->company,
            'publisher_name' => $this->faker->company,
            'isbn' => $this->faker->isbn13,
            'copyright_year' => $this->faker->year,
            'date_receive' => $this->faker->date,
            'date_added' => now(),
            'status' => 'New',
        ];
    }
}
