<?php

namespace Database\Seeders;

use App\Models\Annee;
use App\Models\Role;
use App\Models\User;
use App\Models\Classe;
use App\Models\ClasseEtudiant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EtudiantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::all();
        $annee_scolaires = Annee::all();
        $roleEtudiant = $roles->where("label", "etudiant")->first();

        $classes = Classe::all();
        

        foreach($annee_scolaires as $annee_scolaire) {
         
            foreach ($classes as $classe) {
    
                $etudiants = User::factory()->userRole($roleEtudiant)->count(rand(10,20))->create();
              
                foreach ($etudiants as $etudiant) {
                    $etudiant->etudiantsClasses()->attach($classe->id, ['annee_id' => $annee_scolaire->id,'niveau_id' => $classe->niveau_id]);
                    
                }
    
              
            }
        }

    }
}
