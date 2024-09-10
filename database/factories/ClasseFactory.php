<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\Classe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classe>
 */
class ClasseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $classes = require(base_path('data/classes.php'));

        return [
          

        ];
    }

    public function configure(): static
    {


        return $this->afterMaking(function (Classe $classe) {
            
            $roleCoordinateur= Role::where("label","coordinateur")->first();
            $coordinateur=User::factory()->userRole($roleCoordinateur,true)->create();
            $classe->coordinateur_id=$coordinateur->id;
            
            
        });
    }
}
