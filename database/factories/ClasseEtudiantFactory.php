<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClasseEtudiant>
 */
class ClasseEtudiantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           
        ];
        
    }


    public function linkEtudiantClasse($etudiantId)
{
    return $this->state(function (array $attributes) use($etudiantId){
        dump($etudiantId);
        return [
            'user_id' => $etudiantId,
        ];
    });
}
}
