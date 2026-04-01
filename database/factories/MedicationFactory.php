<?php

namespace Database\Factories;

use App\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        $strength = fake()->randomElement(['250mg', '500mg', '20mg', '40mg', '10mg']);

        return [
            'name' => fake()->randomElement([
                'Omeprazole',
                'Amoxicillin',
                'Losartan',
                'Levothyroxine',
                'Sertraline',
            ]).' '.$strength.' '.fake()->randomElement(['Capsules', 'Tablets', 'Film-coated tablets']),
            'lot_number' => fake()->unique()->numerify('######'),
        ];
    }
}
