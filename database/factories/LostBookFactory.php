<?php

namespace Database\Factories;

use App\Models\LostBook;
use App\Models\Member;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class LostBookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LostBook::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'book_id' => function () {
                return Book::inRandomOrder()->first()->book_id ?? $this->faker->numberBetween(1, 100);
            },
            'isbn' => function () {
                return Book::inRandomOrder()->first()->isbn ?? $this->faker->isbn13();
            },
            'member_no' => function () {
                return Member::inRandomOrder()->first()->member_id ?? $this->faker->numberBetween(1, 100);
            },
            'date_lost' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }
}