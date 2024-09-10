<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Salle;
use App\Models\Classe;
use App\Models\Module;
use App\Models\TypeSeance;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Arr;
use App\Models\EtudiantParent;
use Illuminate\Database\Seeder;
use Database\Seeders\DroppeSeeder;
use Database\Seeders\EnseignantSeeder;
use Database\Seeders\TypeseanceSeeder;
use Database\Seeders\YearSegmentSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $files = Storage::allFiles('/public/users');
      

       // Boucle sur les fichiers et supprimer ceux qui ne correspondent pas au modèle
       $pattern = "/sample-/";
        foreach ($files as $file) {
            if (!preg_match($pattern, basename($file))) {
              
                Storage::delete($file);
       
            }
        }

        $this->call([
            AnneeSeeder::class,
            RoleSeeder::class,
            NiveauSeeder::class,
            FiliereSeeder::class,
            ModuleSeeder::class,
            TypeseanceSeeder::class,
            ClasseSeeder::class,
            SalleSeeder::class,
            EnseignantSeeder::class,
            ClasseModuleSeeder::class,
            // ClasseEnseignantSeeder::class,
            EtudiantSeeder::class,
            TimetableSeeder::class,
            DroppeSeeder::class,
            YearSegmentSeeder::class,

        ]);
    }
}
