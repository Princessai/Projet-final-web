<?php

namespace Database\Seeders;

use App\Enums\roleEnum;
use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = require(base_path('data/modules.php'));
        $roleEnseignantId = Role::where('label', roleEnum::Enseignant->value)->first()->id;
        foreach ($modules as $module) {
            Module::factory()->create(['label' => $module]);
        }
    }
}
