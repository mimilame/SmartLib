<?php

namespace Database\Factories;

use App\Models\BorrowDetails;
use App\Models\Book;
use App\Models\Borrow;
use Illuminate\Database\Eloquent\Factories\Factory;

class BorrowDetailsFactory extends Factory
{
    protected $model = BorrowDetails::class;
    
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'borrow_id' => Borrow::factory(),
            'borrow_status' => $this->faker->randomElement(['pending', 'returned']),
            'date_return' => $this->faker->optional(0.7)->dateTime(),
        ];
    }
    
    // For returned books
    public function returned()
    {
        return $this->state(function (array $attributes) {
            return [
                'borrow_status' => 'returned',
                'date_return' => $this->faker->dateTime(),
            ];
        });
    }
    
    // For pending books
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'borrow_status' => 'pending',
                'date_return' => '',
            ];
        });
    }
}