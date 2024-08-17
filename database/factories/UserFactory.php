<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\Classe;
use App\Models\Module;
use Illuminate\Support\Str;
use App\Models\ClasseEtudiant;
use App\Models\EtudiantParent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Builder;
 

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * 
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'remember_token' => Str::random(10),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone_number' => fake()->phoneNumber(),

        ];
    }

    public function userRole($roleId, $disableDefaultConfig = false): static
    {
        $roles = Role::all();
        return $this->state(fn (array $attributes) => [

            'role_id' => $roleId,


        ])->afterMaking(function (User $user) use ($roles, $disableDefaultConfig) {
            $roleEtudiantId = $roles->where("label", "etudiant")->first()->id;
            $roleParentId = $roles->where("label", "parent")->first()->id;
            if ($disableDefaultConfig) return;
            if ($user->role_id == $roleEtudiantId) {
                $randomCase = rand(1, 3);
                if ($randomCase == 1) {
                    $user->parent_id = null;
                } else if ($randomCase == 2 && User::where('role_id', $roleParentId)->exists()) {

                    $randomParent = User::where('role_id', $roleParentId)->inRandomOrder()->first();
                    $user->parent_id = $randomParent->id;
                } else {
                    $randomParent = User::factory()->userRole($roleParentId, true)->create();
                    $user->parent_id = $randomParent->id;
                }
            }
        })->afterCreating(function (User $user) use ($disableDefaultConfig, $roles) {

            // $roles=Role::whereIn('label', ["parent",'etudiant'])->get();

            $roleParentId = $roles->where("label", "parent")->first()->id;
            $roleEtudiantId = $roles->where("label", "etudiant")->first()->id;
            $roleEnseignantId = $roles->where("label", "enseignant")->first()->id;
            if ($disableDefaultConfig) return;
            
            // if ($user->role_id == $roleParentId) {

            //     $randomParentChildrenCount = rand(1, 4);
            //     //     EtudiantParent::factory()
            //     //    ->parentChild($user->id)
            //     //    ->count($randomParentChildrenCount)
            //     //    ->create();              

            //     User::factory()
            //         ->userRole($roleEtudiantId, true)
            //         ->count($randomParentChildrenCount)
            //         ->for($user, 'etudiantParent')
            //         ->create();
            // }
        
        });
    }


    // public function configure(): static
    // {
    //     dump('init_config');

    //     // return $this->afterMaking(function (User $user) {


    //     // })->afterCreating(function (User $user) {

    //     //     // $roles=Role::whereIn('label', ["parent",'etudiant'])->get();
    //     //     $roles=Role::all();
    //     //     $roleParentId= $roles->where("label","parent")->first()->id;
    //     //     $roleEtudiantId= $roles->where("label","etudiant")->first()->id;
    //     //     $roleCoordinateurId= $roles->where("label","coordinateur")->first()->id;

    //     //     if($user->role_id==$roleParentId){
    //     //         dump('config',$this->disableDefaultConfig);
    //     //         // dump($this->disableDefaultConfig);
    //     //         if($this->disableDefaultConfig)return;
    //     //         $randomParentChildrenCount = 1;
    //     //         EtudiantParent::factory()
    //     //        ->parentChild($user->id)
    //     //        ->count($randomParentChildrenCount)
    //     //        ->create();

    //     //    }



    //     // });
    // }
    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
