<?php

namespace Database\Factories;

use App\Models\Salle;
use App\Enums\seanceStateEnum;
use App\Enums\absenceStateEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seance>
 */
class SeanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'attendance' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function ($seance) {
            $isAttendance = ($seance->etat == seanceStateEnum::Done->value) ? true : false;
            $seance->attendance = $isAttendance;
        });
    }

    public static $staticProp = 0;

    public function createAbsentStudent($classe, $annee_scolaire_id): Factory
    {

        return $this->afterCreating(function ($seance) use ($classe, $annee_scolaire_id) {

            dump('after creating in seance factory');
            dump($seance->etat);
            dump(seanceStateEnum::Done->value);

            if ($seance->etat == seanceStateEnum::Done->value) {

                    $etudiants = $classe->etudiants()->wherePivot('annee_id', $annee_scolaire_id)->distinct()->get();
                    // self::$staticProp++;
                    // dump('static',self::$staticProp);
                    // if(self::$staticProp==1){
                    //     dump('etudiantss_seance_factory',$etudiants->random(3));

                    // }



                    $randomAbsentStudents = $etudiants->random(rand(1, ceil($etudiants->count() / 2)));
                    $absenceStateCases = absenceStateEnum::cases();

                    $randomAbsenceState = fake()->randomElement($absenceStateCases);
                    $coordinateur_id = null;

                    if ($randomAbsenceState->value == absenceStateEnum::justified->value) {
                        $coordinateur_id = $classe->coordinateur->id;
                    }
                    foreach ($randomAbsentStudents as $randomAbsentStudent) {
                        $randomAbsentStudent->etudiantAbsences()->create([
                            'etat' => $randomAbsenceState->value,
                            'seance_id' => $seance->id,
                            'annee_id' => $annee_scolaire_id,
                            'created_at' => $seance->date,
                            'coordinateur_id' => $coordinateur_id,

                        ]);
                    }

                    $randomAbsentStudentsIds = $randomAbsentStudents->pluck('id');
                    $filteredStudents = $etudiants->filter(function ($etudiant) use ($randomAbsentStudentsIds) {
                        return !$randomAbsentStudentsIds->contains($etudiant->id);
                    });

                    $randomLateStudents = $filteredStudents->random(rand(1, ceil($filteredStudents->count() / 3)));

                    foreach ($randomLateStudents as $randomLateStudent) {
                        $randomLateStudent->retardEtudiants()->create([
                            'seance_id' => $seance->id,
                            'created_at' => $seance->date,
                            'annee_id' => $annee_scolaire_id,
                        ]);
                    }
                
                $seanceHours = ceil($seance->heure_debut->diffInHours($seance->heure_fin));
                //    dump('test',$classe->modules()->where('module_id',$seance->module_id)->first()) ;


                $classe->modules()->syncWithoutDetaching([
                    $seance->module_id
                ]);



                $classe->modules()->wherePivot('annee_id', $annee_scolaire_id)->updateExistingPivot($seance->module_id, [
                    'nbre_heure_total' => DB::raw("nbre_heure_total + $seanceHours"),
                    'nbre_heure_effectue' => DB::raw("nbre_heure_effectue + $seanceHours"),
                ]);

                $baseQuery = $classe->modules()->wherePivot('annee_id', $annee_scolaire_id)->wherePivot('module_id', $seance->module_id)->first()->pivot;
                $isThereModuleCourseHours = $baseQuery->courseHours()->where('typeseance_id', $seance->typeseance_id)->exists();




                if (!$isThereModuleCourseHours) {
                    $baseQuery->courseHours()->create([
                        'typeseance_id' => $seance->typeseance_id,
                        'nbre_heure_effectue' => $seanceHours,
                    ]);
                } else {
                    $baseQuery->courseHours()->where('typeseance_id', $seance->typeseance_id)->incrementEach([
                        'nbre_heure_effectue' => $seanceHours,
                    ]);
                }
                dump('modules de la classe', $isThereModuleCourseHours);
        }
           
        });
    }
}
