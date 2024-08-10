<?php

namespace Database\Seeders;

use App\Models\Niveau;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NiveauSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $niveaux = require(base_path('data/niveaux.php')) ;

        foreach ($niveaux as $niveau) {
            Niveau::create(['label' => $niveau['label'], 'alias' => $niveau['alias']]);
        }
    }
}
