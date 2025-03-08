<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Member::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $types = Type::pluck('borrowertype')->toArray();
        $type = $this->faker->randomElement($types);
        
        // Define designations based on type
        $designations = [
            'Student' => ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Graduate'],
            'Teacher' => ['Faculty', 'Visiting Faculty', 'Professor Emeritus'],
            'Staff' => ['Administrative', 'Technical', 'Support'],
            'Alumni' => ['N/A'],
            'Employee' => ['Full-time', 'Part-time', 'Contract'],
            'Non-Teaching' => ['Administrative', 'Maintenance', 'Security'],
            'Contruction' => ['N/A'],
        ];
        $designation = 'N/A';
        if (isset($designations[$type])) {
            $designation = $this->faker->randomElement($designations[$type]);
        }

        return [
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'address' => $this->faker->address(),
            'contact' => $this->faker->phoneNumber(),
            'type' => $type,
            'designation' => $designation,
            'status' => $this->faker->randomElement(['Active', 'Banned', 'Expired']),
            'membership_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'borrowed_books_count' => $this->faker->numberBetween(0, 20),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'email' => $this->faker->email(),
            'id_number' => $this->faker->unique()->numerify('ID-#######'),
            'department' => $this->faker->randomElement([
                'Arts and Humanities', 'Science', 'Engineering', 'Medicine', 
                'Law', 'Business', 'Education', 'Social Sciences', 'N/A'
            ]),
        ];
        
    }
}