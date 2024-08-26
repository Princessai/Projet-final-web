<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Annee;
use App\Models\Classe;
use App\Enums\seanceStateEnum;
use App\Http\Resources\ClasseStudentsAbsencesResource;
use Illuminate\Database\Eloquent\Collection;

include_once(base_path('utilities\copyCollection.php'));
include_once(base_path('utilities\seeder\seanceDuration.php'));


function isSeanceInYearSegments($seance, $yearSegments)
{
    $seanceStart = Carbon::parse($seance->heure_debut);

    $filtered =  $yearSegments->filter(function ($yearSegment, $key) use ($seanceStart) {

        $yearSegmentStart = Carbon::parse($yearSegment->start);
        $yearSegmentEnd = Carbon::parse($yearSegment->end);

        //  apiSuccess(data: ['key' => $key ,'yearS' => $yearSegmentStart, 'yearSegmentEnd'=>$yearSegmentEnd, 'seanceStart' => $seanceStart])->send();
        //     die();
        return  $seanceStart->greaterThanOrEqualTo($yearSegmentStart)  && $seanceStart->lessThanOrEqualTo($yearSegmentEnd);
    });

    return $filtered;
}

class ClasseService
{
    public function getClassCurrentStudent($classe,$currentYear=null){
        if($currentYear==null){
        $currentYear = (new AnneeService())->getCurrentYear();                

        }
        return $classe->etudiants()->wherePivot('annee_id', $currentYear->id)->get();
    }
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
        if ($classesModules instanceof Collection) {
            foreach ($classesModules as $classesModule) {
    
                $nbre_heure_effectue += $classesModule->pivot->nbre_heure_effectue;
            }
            
        }else {
            $nbre_heure_effectue = $classesModules->pivot->nbre_heure_effectue;
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

    public function getClasseAttendanceRates($classe, $timestamp1, $timestamp2, $module_id = null)
    {

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;


        if ($timestamp1 === null && $timestamp2 === null) {
            $currentYear = Annee::latest()->first();

            $baseQuery = $classe->modules()->wherePivot('annee_id', $currentYear->id);

            if ($module_id !== null) {
                $baseQuery = $baseQuery->where('modules.id', $module_id);
            }

            $nbre_heure_effectue = $this->getClasseModulesWorkedHours($baseQuery->get());
        } else if ($timestamp1 !== null && $timestamp2 !== null) {

            $baseQuery = $classe->seances()->where('etat', seanceStateEnum::Done->value);

            if ($module_id !== null) {
                $baseQuery = $baseQuery->where('module_id', $module_id);
            }
            $classeSeances = $baseQuery->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();
            $nbre_heure_effectue = $this->getClasseSeancesWorkedHours($classeSeances);
        } else if ($timestamp1 !== null && $timestamp2 === null) {

            $baseQuery = $classe->seances()->where('etat', seanceStateEnum::Done->value);
            if ($module_id !== null) {
                $baseQuery = $baseQuery->where('module_id', $module_id);
            }

            $classeSeances = $baseQuery->where('heure_debut', '>', $timestamp1)->get();
            $nbre_heure_effectue = $this->getClasseSeancesWorkedHours($classeSeances);
        }


        $classeStudents = $classe->etudiants;
        $classeStudentsCount = $classeStudents->count();
        $strudentAttendanceRate = [];
        $classeStudentsAttendanceRate = 0;
        foreach ($classeStudents as $classeStudent) {

            // return apiSuccess(data:new ClasseStudentsAbsencesResource($classeStudent, $nbre_heure_effectue));
            $apiResource = new ClasseStudentsAbsencesResource($classeStudent, $nbre_heure_effectue, $module_id);


            $attendanceRate = $apiResource->toArray(request())['attendanceRate'];
            //  return apiSuccess( $attendanceRate );
            $classeStudentsAttendanceRate += $attendanceRate;

            $strudentAttendanceRate[] =  $apiResource;
        }

        $classeAttendanceRate = round($classeStudentsAttendanceRate / $classeStudentsCount, 2);

        return ['strudentAttendanceRate' => $strudentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate, 'workedHours' => $nbre_heure_effectue];
    }

    public function getYearSegmentsWorkedHours($classe, $yearSegments, $currentYear, $typeSeances)
    {


        $yearSegmentsCopy =  recursiveClone($yearSegments);

        $seances = $classe->seances()->where([
            'annee_id' => $currentYear->id,
            'etat' => seanceStateEnum::Done->value
        ])->get();







        foreach ($seances as $seance) {

            $seancesYearSegments = isSeanceInYearSegments($seance, $yearSegmentsCopy);

            if ($seancesYearSegments->isEmpty()) continue;



            $seanceStart = Carbon::parse($seance->heure_debut);
            $seanceEnd = Carbon::parse($seance->heure_fin);
            $seanceDuration = seanceDuration($seanceEnd, $seanceStart);

            $seanceType = $typeSeances->where('id', $seance->type_seance_id)->first();



            $currentSeanceYearSegment = $seancesYearSegments->first();


            if (!isset($currentSeanceYearSegment->workedHours)) {
                $currentSeanceYearSegment->workedHours = ['all'=>0];
            }



            if (isset($currentSeanceYearSegment->workedHours)) {

                $workedHours = $currentSeanceYearSegment->workedHours;
                $workedHours['all'] += $seanceDuration; 
                $label = $seanceType->label;
              
                if (isset($workedHours[$label])) {
                    $workedHours[$label] += $seanceDuration;
                } else {

                   $workedHours[$label] = $seanceDuration;
                }
                $currentSeanceYearSegment->workedHours=$workedHours;
                // apiSuccess([$workedHours,$currentSeanceYearSegment->workedHours ])->send();
                // die();
            
            }
            //     $currentSeanceYearSegment->workedHours += $seanceDuration;
            // } else {
            //     $currentSeanceYearSegment->workedHours = $seanceDuration;
            // }
        }

        return $yearSegmentsCopy;
    }
}
