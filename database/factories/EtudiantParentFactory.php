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
    $roleEtudiantId=Role::where('label','etudiant' )->first()->id;
    return $this->state(function (array $attributes) use($parentId,$roleEtudiantId) {
        $child=User::factory()->userRole($roleEtudiantId)->create();
       
        return [
            'parent_id' => $parentId,
            "etudiant_id"=>$child->id
            
        ];
    });
}

}
