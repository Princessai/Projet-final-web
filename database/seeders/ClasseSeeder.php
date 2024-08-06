<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Classe;
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
        foreach( $classes as $classe){
            $classe=Classe::factory()->create(['label'=>$classe]);
            
        }
    }
}
