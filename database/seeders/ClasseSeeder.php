<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\Filiere;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes =  require(base_path('data/classes.php'));

        $niveaux = Niveau::all();
        $filieres = Filiere::all();
    
        foreach( $classes as $classe){
            $niveau = $niveaux->where('label', $classe['level']['label'])->first();
            $filiere = $filieres->where('label', $classe['section']['label'])->first();
            
          
            $classe=Classe::factory()->create(['label'=>$classe['label'], 'niveau_id' => $niveau->id, 'filiere_id' => $filiere->id ]);
            
        }
    }
}
