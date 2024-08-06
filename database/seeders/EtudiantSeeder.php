<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Classe;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EtudiantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::all();
        $roleEtudiantId = $roles->where("label", "etudiant")->first()->id;

        $classes = Classe::all();

        $classeSequences = [];
        foreach ($classes as $classe) {

            $classeSequences[] = ["classe_id" => $classe->id];
        }

        User::factory()->userRole($roleEtudiantId)->count(20)->sequence(...$classeSequences)->create();
    }
}
