<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Classe;
use App\Enums\seanceStateEnum;
use App\Http\Resources\ClasseStudentsAbsencesResource;

// require(base_path('utilities\seeder\seanceDuration.php'));

class ClasseService
{

    public function getStudentMissedAndWorkedHours($seancesClasses, $student_id)
    {
        $nbre_heure_effectue = 0;
        $missingHoursCount = 0;

        foreach ($seancesClasses as $seance) {
            $startHour =  Carbon::parse($seance->heure_debut);
            $endHour =  Carbon::parse($seance->heure_fin);
            $duree = seanceDuration($endHour, $startHour);
            $nbre_heure_effectue += $duree;

            $studentAbsence = $seance->absences()->where('user_id', $student_id)->first();

            if (!is_null($studentAbsence)) {
                //  return apiSuccess(data: $studentAbsence);
                $missingHoursCount += $duree;
            }
        }

        return ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount];
    }
    public function getStudentMissedHours($studentAbsences)
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

    public function getClasseModulesWorkedHours($classesModules)
    {
        $nbre_heure_effectue = 0;
        foreach ($classesModules as $classesModule) {

            $nbre_heure_effectue += $classesModule->pivot->nbre_heure_effectue;
        }

        return $nbre_heure_effectue;
    }

    public function getClasseSeancesWorkedHours($seancesClasses)
    {
        $nbre_heure_effectue = 0;

        foreach ($seancesClasses as $seance) {
            $startHour =  Carbon::parse($seance->heure_debut);
            $endHour =  Carbon::parse($seance->heure_fin);
            $duree = seanceDuration($endHour, $startHour);
            $nbre_heure_effectue += $duree;
        }

        return $nbre_heure_effectue;
    }

    public function getClasseAttendanceRates($classe, $timestamp1, $timestamp2)
    {

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;


        if ($timestamp1 === null && $timestamp2 === null) {

            $nbre_heure_effectue = $this->getClasseModulesWorkedHours($classe->modules);
        } else if ($timestamp1 !== null && $timestamp2 !== null) {

            $classeSeances = $classe->seances()->where('etat', seanceStateEnum::Done->value)->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();
            $nbre_heure_effectue = $this->getClasseSeancesWorkedHours($classeSeances);
        } else if ($timestamp1 !== null && $timestamp2 === null) {

            $classeSeances = $classe->seances()->where('etat', seanceStateEnum::Done->value)->where('heure_debut', '>', $timestamp1)->get();
            $nbre_heure_effectue = $this->getClasseSeancesWorkedHours($classeSeances);
        }


        $classeStudents = $classe->etudiants;
        $classeStudentsCount = $classeStudents->count();
        $strudentAttendanceRate = [];
        $classeStudentsAttendanceRate = 0;
        foreach ($classeStudents as $classeStudent) {

            // return apiSuccess(data:new ClasseStudentsAbsencesResource($classeStudent, $nbre_heure_effectue));
            $apiResource = new ClasseStudentsAbsencesResource($classeStudent, $nbre_heure_effectue);


            $attendanceRate = $apiResource->toArray(request())['attendanceRate'];
            //  return apiSuccess( $attendanceRate );
            $classeStudentsAttendanceRate += $attendanceRate;

            $strudentAttendanceRate[] =  $apiResource;
        }

        $classeAttendanceRate = round($classeStudentsAttendanceRate / $classeStudentsCount, 2);

        return ['strudentAttendanceRate' => $strudentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate];
    }
}
