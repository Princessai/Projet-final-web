<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $modules = require(base_path('data/modules.php'));
        
        foreach ($modules as $module) {
            Module::factory()->create(['label'=>$module]);
        }

    }
}
