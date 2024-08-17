<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EnseignantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = Module::all();
        $roleEnseignantId = Role::where('label', 'enseignant')->first()->id;
        foreach ($modules as $module) {
            $randomModuleNumber = rand(1, 3);
            // $relatedModules=$module;
            $randomModules=$module;
            
            // a modifier !!
            if ($randomModuleNumber> 1) {
                // while (true) {
                //     $randomModules = $modules->random($randomModuleNumber - 1);
                //     $containCurrentModule = $randomModules->contains(function ($randomModule, int $key) use($module) {
                //         return $randomModule->id == $module->id;
                //     });
    
                //     if (!$containCurrentModule) {
                //       break;  
                //     }
                // }
                // $randomModulesId = $randomModules->pluck('id');
                // $randomModulesId[]= $module->id;
                // $relatedModules = $modules->whereIn('id', $randomModulesId);

                $filteredModules = $modules->filter(function ($currentModule) use($module) {
                    return $currentModule->id != $module->id;
                });

                $randomModules = $filteredModules->random($randomModuleNumber - 1);
                $randomModules->push($module);



               
            }

            // $modules->filter(function (User $filteredModule) use ($module, $count, $moduleNumber) {
            //     if ($count > $modulenumber - 1) {
            //         return false;
            //     }
            //     if ($filteredModule->id != $module->id && $count != $modulenumber - 1) {
            //         $count++;
            //         return true;
            //     }
            //     return;
            // });


            $enseignant = User::factory()->userRole($roleEnseignantId)->create();
            $enseignant->enseignantModules()->attach($randomModules);

        }
    }
}
