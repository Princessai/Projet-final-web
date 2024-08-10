<?php

namespace Database\Seeders;

use App\Models\Filiere;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FiliereSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filieres = require(base_path('data/filieres.php')) ;


        foreach ($filieres as $filiere) {

            $alias =isset($filiere['alias']) ? $filiere['alias'] : null;
            Filiere::create(['label' => $filiere['label'], 'alias' => $alias]);
        }
    }

    
}
