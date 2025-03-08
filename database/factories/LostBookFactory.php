<?php

namespace Database\Factories;

use App\Models\LostBook;
use App\Models\Member;
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
            'ISBN' => $this->faker->numberBetween(1000000000, 9999999999),
            'Member_No' => function () {
                return Member::inRandomOrder()->first()->member_id ?? $this->faker->numberBetween(1, 100);
            },
            'Date_Lost' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }
}
