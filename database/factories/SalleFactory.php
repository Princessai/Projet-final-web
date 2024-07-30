<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Salle>
 */
class SalleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        dump("deifintionnn________");
        $salles = require(base_path('data/salles.php'));

        
        return [
            'label' =>   fake()->unique()->randomelement($salles),
        ];
    }
}
