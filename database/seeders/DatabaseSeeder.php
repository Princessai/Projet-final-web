<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Salle;
use App\Models\Classe;
use App\Models\Module;
use App\Models\Typeseance;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Arr;
use App\Models\EtudiantParent;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $modules = require(base_path('data/modules.php'));
        $roles = require(base_path('data/roles.php'));
        $salles = require(base_path('data/salles.php'));
        $typeseances = require(base_path('data/typeseances.php'));
        $classes =  require(base_path('data/classes.php'));



        // User::factory(10)->create();
        $salle_count = count($salles);
        Salle::factory()->count($salle_count)
            ->create();


        $role_count = count($roles);
        $roles = Role::factory()->count($role_count)
            ->create();

        $module_count = count($modules);
        Module::factory()->count($module_count)
            ->create();

        $typeseance_count = count($typeseances);
        Typeseance::factory()->count($typeseance_count)
            ->create();

        // dump($roles);

        $rolesId = $roles->pluck('id');
        $roleSequences = [];
        foreach ($rolesId as $key => $value) {
            $roleSequences[] = ["role_id" => $value];
        }

        dump($roleSequences);
        // new Sequence(
        //     ['admin' => 'Y'],
        //     ['admin' => 'N'],
        // )


        $users = User::factory()
            ->count(10)
            // ->state(function (array $attributes) use ($rolesId) {
            //     return ['role_id' => $rolesId->random()];
            // })
            ->state(new Sequence(
                ...$roleSequences

            ))
            ->create();

        $roleEtudiantId = $roles->where('label', 'etudiant')->first()->id;
        $userEtudiant = $users->where('role_id', $roleEtudiantId);

        $roleParentId = $roles->where('label', 'parent')->first()->id;
        $userParent = $users->where('role_id', $roleParentId);

        $roleCoordinateurId = $roles->where('label', 'coordinateur')->first()->id;
        $userCoordinateurs = $users->where('role_id', $roleCoordinateurId);


        // dump($roleEtudiantId);

        $userEtudiantId = $userEtudiant->pluck('id');
        $userParentId = $userParent->pluck('id');
        $userCoordinateursId = $userCoordinateurs->pluck('id');


        $etudiantParent = EtudiantParent::factory()->count(rand(1, $userEtudiant->count()))
            ->state(function (array $attributes) use ($userEtudiantId, $userParentId) {
                dump($userEtudiantId->toArray());



                return [
                    'etudiant_id' => fake()->unique()->randomelement($userEtudiantId->toArray()),
                    'parent_id' => $userParentId->random(),
                ];
            })
            ->create();

        $classe_count = count($classes);
        $classes = Classe::factory()->count($classe_count)
            ->state(function (array $attributes) use ($userCoordinateursId) {

                return [
                    'coordinateur_id' => fake()->randomelement($userCoordinateursId->toArray()),
                ];
            })
            ->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
