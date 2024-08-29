<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\Callback;
use App\Models\Absence;
use App\Services\AnneeService;
use App\Enums\absenceStateEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class StudentService
{

    public function AttendancePercentageCalc($missedHours, $workedHours)
    {
        if ($workedHours != 0) {
            $absencePercentage = ($missedHours * 100) / $workedHours;
        } else {
            apiError(message: 'no courses has been done for this interval')->send();
            die();
        }

        $attendanceRate = round(100 - $absencePercentage, 2);

        return $attendanceRate;
    }

    public function getCurrentClasse($user, $currentYear)
    {
        $studentClasses = $this->getCurrentClasses($user, $currentYear);
        return $studentClasses->last();
    }


    public function getCurrentClasses($user, $currentYear)
    {
        return  $studentClasses = $user->etudiantsClasses()
            ->wherePivot('annee_id', $currentYear->id)->get();
    }

    public function getModuleAbsences($student, $module_id, $currentYear = null, $callback = null, $etat = null)
    {

        if ($currentYear == null) {
            $currentYear = (new AnneeService)->getCurrentYear();
        }
        if ($student->relationLoaded('etudiantAbsences')) {
            $absencesQuery = $student->etudiantAbsences()->whereHas('seance', function ($query) use ($currentYear, $module_id, $callback) {
                $query->where([
                    'annee_id' => $currentYear->id,
                    'module_id' => $module_id,
                ]);
                if ($callback !== null) {
                    $callback($query);
                }
            });
        } else {
            $absencesQuery = Absence::whereHas('seance', function ($query) use ($module_id, $currentYear, $callback) {

                $query = $query->where([
                    'module_id' => $module_id,
                    'annee_id' => $currentYear->id
                ]);
                if ($callback !== null) {
                    $callback($query);
                }
            })
                ->where([
                    'user_id' => $student->id,
                ]);
        }





        if ($etat !== null) {
            $absencesQuery = $absencesQuery->where('etat', $etat);
        }



        return $absencesQuery->get();
    }


    public function getMissedHours($student_id, $currentYear_id, $module_id = null, $callback = null)
    {

        if ($student_id instanceof Builder) {

            return $student_id->withSum(['etudiantAbsences as missingHoursSum' => function ($query) use ($currentYear_id, $module_id, $callback) {
                $baseInnerQuery = $query->where('annee_id', $currentYear_id);
                if ($module_id !== null) {
                    $baseInnerQuery = $baseInnerQuery->where('module_id', $module_id);
                }
                if ($callback !== null) {
                    $callback($baseInnerQuery);
                }


               
            }], 'duree');

            
        }

        $baseWhereClause = ['annee_id' => $currentYear_id, 'user_id' => $student_id];
        if ($module_id !== null) {
            $baseWhereClause['module_id'] = $module_id;
        }
        $absencebaseQuery = Absence::where($baseWhereClause);

        if ($callback !== null) {
            $callback($absencebaseQuery);
        }

        return (int) $absencebaseQuery->sum('duree');
    }

    public function calcMissedHours(Collection $studentAbsences)
    {
        $missingHoursCount = 0;

        foreach ($studentAbsences as $studentAbsence) {
            $seance = $studentAbsence->seance;
            $startHour =  Carbon::parse($seance->heure_debut);
            $endHour =  Carbon::parse($seance->heure_fin);

            $duree = seanceDuration($endHour, $startHour);


            $missingHoursCount += $duree;
        }

        return  $missingHoursCount;
    }
}
