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
        $modules = Module::with('enseignants')->get();
        $classes = Classe::all();
        $annee = Annee::latest()->first();
        $classesTeachers = collect([]);

        foreach ($modules as $module) {

            $randomClasseNumber =  rand(1,  ceil($classes->count() / 2));

            $randomClasses = $classes->random($randomClasseNumber);
            $randomClassesArr = [];
            $moduleTeachers = $module->enseignants;
            foreach ($randomClasses as $classe) {

                $randomModuleTeacher = $moduleTeachers->random(1)->first();

                $randomClassesArr[$classe->id] = ['annee_id' => $annee->id, "user_id" => $randomModuleTeacher->id];

                $isClasseTeacher = false;
                if (isset($classesTeachers[$classe->id])) {
                    $isClasseTeacher = $classesTeachers[$classe->id]->contains(function ($teacherId) use ($randomModuleTeacher) {
                        return $teacherId == $randomModuleTeacher->id;
                    });
                }

                if ($isClasseTeacher == false) {

                    $classe->enseignants()->attach($randomModuleTeacher);

                    if (isset($classesTeachers[$classe->id])) {
                        $classesTeachers[$classe->id]->push($randomModuleTeacher->id);
                    } else {
                        $classesTeachers[$classe->id] = collect([$randomModuleTeacher->id]);
                    }
                }
            }


            // $module->classes()->attach($randomClasses,['annee_id'=>$annee->id]);
       
            $module->classes()->attach($randomClassesArr);
        }



        $classesWithoutModules = Classe::doesntHave('modules')->get();


        foreach ($classesWithoutModules as $classesWithoutModule) {

            $randomModules = $modules->random(rand(3, $modules->count()));

            $randomModulesArr = [];
            foreach ($randomModules as $randomModule) {

                $moduleTeachers = $randomModule->enseignants;
                $randomModuleTeacher = $moduleTeachers->random(1)->first();
                $randomModulesArr[$randomModule->id] = ['annee_id' => $annee->id, "user_id" => $randomModuleTeacher->id];


                $isClasseTeacher = false;
                if (isset($classesTeachers[$classe->id])) {
                    $isClasseTeacher = $classesTeachers[$classe->id]->contains(function ($teacherId) use ($randomModuleTeacher) {
                        return $teacherId == $randomModuleTeacher->id;
                    });
                }

                if ($isClasseTeacher == false) {

                    $classe->enseignants()->attach($randomModuleTeacher);

                    if (isset($classesTeachers[$classe->id])) {
                        $classesTeachers[$classe->id]->push($randomModuleTeacher->id);
                    } else {
                        $classesTeachers[$classe->id] = collect([$randomModuleTeacher->id]);
                    }
                }
                
            }

            $classesWithoutModule->modules()->attach($randomModulesArr);
        }
    }
}
