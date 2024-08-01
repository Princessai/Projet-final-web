<?php

namespace Database\Seeders;

use App\Models\Salle;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class SalleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salles = require(base_path('data/salles.php'));
        
        foreach ($salles as $salle) {
          Salle::factory()->create(['label'=>$salle]);
        }

      
    }
}
