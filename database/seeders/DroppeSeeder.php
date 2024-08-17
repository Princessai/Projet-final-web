<?php

namespace Database\Seeders;

use App\Models\Annee;
use App\Models\Classe;
use App\Models\Droppe;
use App\Models\Absence;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DroppeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = Classe::with(['modules', 'etudiants'])->get();
        // $annee_scolaire = Annee::latest()->first();

        foreach ($classes as $classe) {
            // where(function (Builder $query) {
            //     return $query->where('active', 1)
            //                  ->orWhere('votes', '>=', 100);
            // })
            $etudiantsAbsents = $classe->etudiants()->where(function ($query) {
                return $query->has('etudiantAbsences');
            })->get();
            dump('etudiant Absent', $etudiantsAbsents->pluck('id'));
            $classeModules = $classe->modules;


            foreach ($etudiantsAbsents as $etudiantsAbsent) {

                // dump($etudiantsAbsent->etudiantAbsences()->seance()->groupBy('module_id'));

                $etudiantsAbsences = $etudiantsAbsent->etudiantAbsences->groupBy(['annee_id', function ($item, int $key) {
                    return $item->seance->module_id;
                }]);
                // dump("les absences de l etudiant {$etudiantsAbsent->id} pour :", $etudiantsAbsences);

                foreach ($etudiantsAbsences as $annee_id => $modules) {
                    dump("annee id ____", $annee_id);
                    foreach ($modules as $module_id => $moduleAbsences) {
                        dump("module id____", $module_id);
                        dump("module absencee", $moduleAbsences->pluck('seance_id'));
                        $moduleMissingHours = 0;
                        $classeModule = $classe->modules->where('id', $module_id)->first();
                        $nbr_total_effectue =  $classeModule->pivot->nbre_heure_effectue;

                        dump($nbr_total_effectue);
                        foreach ($moduleAbsences as $moduleAbsence) {
                            $moduleMissingHours += $moduleAbsence->seance->duree;
                        }

                        if ($nbr_total_effectue != 0) {
                            $absencePercentage = round(($moduleMissingHours * 100) / $nbr_total_effectue, 2);
                            $presencePercentage = 100 - $absencePercentage;

                            if ($presencePercentage <= 30) {
                                dump('dropeeeeee ' . $etudiantsAbsent->id);
                                dump('je suis dans le if');
                                Droppe::create([
                                    'user_id' => $etudiantsAbsent->id,
                                    'module_id' => $classeModule->id,
                                    'annee_id' => $annee_id
                                ]);
                            }
                            dump("pourcentage de presence", $presencePercentage);
                        }
                    }
                }


                // foreach ($classeModules as $classeModule) {

                //     $etudiantsAbsences = $etudiantsAbsent->etudiantAbsences()->where(function ($query) use ($classeModule) {

                //         return $query->whereHas('seance', function ($query) use ($classeModule) {
                //             $query->where('module_id', $classeModule->id);
                //         });
                //     })->get();

                //     $missingHours = 0;
                //     foreach ($etudiantsAbsences as $etudiantAbsence) {
                //         $missingHours += $etudiantAbsence->seance->duree;
                //     }

                //     $nbr_total_effectue =  $classeModule->pivot->nbre_heure_effectue;

                //     dump("les absences de l etudiant {$etudiantsAbsent->id} pour :" . $classeModule->label, $etudiantsAbsences->pluck('seance_id'));
                //     dump("nombre d'heure total", $nbr_total_effectue);

                //     if ($nbr_total_effectue != 0) {
                //         $absencePercentage = round(($missingHours * 100) / $nbr_total_effectue, 2);
                //         $presencePercentage = 100 - $absencePercentage;

                //         if ($presencePercentage <= 30) {
                //             dump('dropeeeeee '.$etudiantsAbsent->id);
                //             Droppe::create([
                //                 'etudiant_id' => $etudiantsAbsent->id,
                //                 'module_id' => $classeModule->id,
                //                 'annee_id' => $annee_scolaire->id
                //             ]);
                //         }
                //         dump("pourcentage de presence", $presencePercentage);
                //     }

                //     dump('missing hours', $missingHours);
                // }
            }

            dump('classe : ' . $classe->id);
        }
    }
}
