<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\EtudiantParent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EtudiantParent>
 */
class EtudiantParentFactory extends Factory
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

    public function parentChild($parentId): Factory
{
    $roleEtudiant=Role::where('label','etudiant' )->first();
    return $this->state(function (array $attributes) use($parentId,$roleEtudiant) {
        $child=User::factory()->userRole($roleEtudiant)->create();
       
        return [
            'parent_id' => $parentId,
            "etudiant_id"=>$child->id
            
        ];
    });
}

}
