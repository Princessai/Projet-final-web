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
use Database\Seeders\DroppeSeeder;
use Database\Seeders\EnseignantSeeder;
use Database\Seeders\TypeseanceSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       
        
    
        $this->call([
            AnneeSeeder::class,
            RoleSeeder::class,
            NiveauSeeder::class,
            FiliereSeeder::class,
            ModuleSeeder::class,
            TypeseanceSeeder::class,
            ClasseSeeder::class,
            SalleSeeder::class,
            ClasseModuleSeeder::class,
            EnseignantSeeder::class,
            ClasseEnseignantSeeder::class,
            EtudiantSeeder::class,
            TimetableSeeder::class,
            DroppeSeeder::class,
          
        ]);
        
        // $user = User::where('role_id', 3)->first();
        //     $modules = Module::whereDoesntHave('enseignants', function ($query) use ( $user) {
        //             $query->where('users.id' ,$user->id);
        //         })->get();
        // dump('databaseseeder',$modules);
      

            
        // $classes_count = count($classes);

        // Classe::factory()
        // ->count($classes_count)     
        // ->create();

      
        // $rolesId = $roles->pluck('id');
        // $roleEtudiantId = $roles->where('label', 'etudiant')->first()->id;
        // $roleSequences = [];
        // foreach ($roles as $role) {       
        //     if($role->label!="coordinateur"){
        //         $roleSequences[] = ["role_id" => $role->id];
        //     }  
               
        // }

       
    
        // $users = User::factory()
        //     ->count(100)
        //     ->state(new Sequence(
        //         ...$roleSequences
        //     ))
        //     ->create();
        $roles= Role::all();

        $roleEtudiantId = $roles->where('label', 'etudiant')->first()->id;
        // $userEtudiant = $users->where('role_id', $roleEtudiantId);
        // $userEtudiantId = $userEtudiant->pluck('id');

        $roleParentId = $roles->where('label', 'parent')->first()->id;

        // $userParent = $users->where('role_id', $roleParentId);
        $randomParentCount = rand(1, 10);
        $randomParentChildrenCount = rand(1, 4);
        // $usersParent = User::factory()
        //     ->userRole($roleParentId)
        //     ->count($randomParentCount)
        //     ->create();

            // $usersParent = User::factory()
            // ->userRole($roleParentId)
            // ->count($randomParentCount)
            // ->create();
 

            // ->each(function ($usersParent) use($roleEtudiantId,$randomParentChildrenCount) {
            //     EtudiantParent::factory()
            //     ->parentChild($usersParent->id,$roleEtudiantId)
            //     ->count($randomParentChildrenCount)->create();
            //     });

            // EtudiantParent::factory()
            // ->state(
            //     function (array $attributes) use($roleEtudiantId){
            //         $child=User::factory()
            //         ->state([
            //             'role_id' => $roleEtudiantId
            //         ])->create();

            //         return [
            //             'etudiant_id' => $child->id,
            //         ];
            //     }
            // )->count(rand(1,10)),
        // $roleCoordinateurId = $roles->where('label', 'coordinateur')->first()->id;
        // $userCoordinateurs = $users->where('role_id', $roleCoordinateurId);


        // // dump($roleEtudiantId);


        // $userParentId = $userParent->pluck('id');
        // $userCoordinateursId = $userCoordinateurs->pluck('id');


        // $etudiantParent = EtudiantParent::factory()->count(rand(1, $userEtudiant->count()))
        //     ->state(function (array $attributes) use ($userEtudiantId, $userParentId) {
        //         dump($userEtudiantId->toArray());



        //         return [
        //             'etudiant_id' => fake()->unique()->randomelement($userEtudiantId->toArray()),
        //             'parent_id' => $userParentId->random(),
        //         ];
        //     })
        //     ->create();

        // $classe_count = count($classes);
        // $classes = Classe::factory()->count($classe_count)
        //     ->state(function (array $attributes) use ($userCoordinateursId) {

        //         return [
        //             'coordinateur_id' => fake()->randomelement($userCoordinateursId->toArray()),
        //         ];
        //     })
        //     ->create();

        // // User::factory()->create([
        // //     'name' => 'Test User',
        // //     'email' => 'test@example.com',
        // // ]);
    }
}
