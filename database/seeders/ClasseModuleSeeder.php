<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Module;
use App\Models\CourseHour;
use App\Models\Typeseance;

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
        $typeseances = Typeseance::all();

        $typeseancesSequence = [];
        foreach ($typeseances as  $typeseance) {
            $typeseancesSequence[] = ['typeseance_id' => $typeseance->id];
        }

        foreach ($classes as $classe) {
            $randomModuleNumber = rand(1, $modules->count());

            $randomModules = $modules->random($randomModuleNumber);

            foreach ($randomModules as $randomModule) {
                $nbre_heure_total = 0;
                $course_hours = CourseHour::factory()->count($typeseances->count())->state(function (array $attributes) use (&$nbre_heure_total) {
                    $randhours = rand(8, 30);
                    // dump('$randhours', $randhours);

                    $nbre_heure_total += $randhours;
                    // dump( 'acc value' ,$nbre_heure_total);

                    return ['nbre_heure_total' => $randhours];
                })->sequence(...$typeseancesSequence)->make();
                // dump("nombretotal",$nbre_heure_total);
                $classe->modules()->attach($randomModule, ['nbre_heure_total' => $nbre_heure_total]);
                $classe_module_id =$classe->modules->first()->pivot->id;
                foreach ($course_hours as $course_hour) {
                    $course_hour->classe_module_id =$classe_module_id;
                    $course_hour->save();
                }
              
              
            }
        }
    }
}
