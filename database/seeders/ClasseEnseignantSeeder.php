<?php

namespace Database\Seeders;

use App\Models\Annee;
use App\Models\Classe;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClasseEnseignantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

      
        $classes = Classe::with([
            'modules' => [
                'enseignants', // relation entre module et enseignant
            ],
        ])->get();

        foreach ($classes as $classe) {
            $classeModules= $classe->modules;
            foreach ($classeModules as $classeModule) {
                $classeModuleEnseignants = $classeModule->enseignants->random();
                $classe->enseignants()->attach($classeModuleEnseignants);
            }
            
        }
    }
}
