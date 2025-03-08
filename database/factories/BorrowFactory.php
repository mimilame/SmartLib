<?php

namespace Database\Factories;

use App\Models\Borrow;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class BorrowFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Borrow::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'member_id' => Member::factory(), // Generates a related member if MemberFactory exists
            'date_borrow' => $this->faker->dateTimeBetween('-1 month', 'now'), // Random past borrow date
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'), // Future due date
        ];
    }
}
