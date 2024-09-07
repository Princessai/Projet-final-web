<?php

namespace Database\Seeders;

use App\Models\Annee;
use App\Models\Classe;
use App\Models\Module;
use App\Models\CourseHour;

use App\Models\TypeSeance;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class ClasseModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = Module::all();
        $classes = Classe::all();
        $annee = Annee::latest()->first();
   
        foreach ($modules as $module) {

            $randomClasseNumber =  rand(1,  ceil($classes->count()/2));

            $randomClasses = $classes->random($randomClasseNumber);

            $module->classes()->attach($randomClasses,['annee_id'=>$annee->id]);

        }



        $classesWithoutModules = Classe::doesntHave('modules')->get();


        foreach ($classesWithoutModules as $classesWithoutModule) {
            $randomModules = $modules->random(rand(3, $modules->count()));

            $classesWithoutModule->modules()->attach($randomModules,['annee_id'=>$annee->id]);
        }

    
    }
}
