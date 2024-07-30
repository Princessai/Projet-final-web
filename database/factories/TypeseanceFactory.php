<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Typeseance>
 */
class TypeseanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   
    public function definition(): array
    {
  
        $typeseances = require(base_path('data/typeseances.php'));

        return [
            'label' => fake()->unique()->randomelement($typeseances),
        ];
    }
}
