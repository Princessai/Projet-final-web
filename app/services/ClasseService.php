<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Annee;
use App\Models\Classe;
use App\Models\Absence;
use App\Enums\seanceStateEnum;
use App\Enums\absenceStateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Resources\ClasseStudentsAbsencesResource;

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
    public function getClassCurrentStudent($classe_id, $currentYear = null)
    {

     

        if ($currentYear == null) {
            $currentYear = (new AnneeService())->getCurrentYear();
        }
        if(!($classe_id instanceof Builder)&&!($classe_id instanceof Model)){

           $classeQuery= Classe::with(['etudiants'=>function($query) use($currentYear){
                $query->with('role');
                $query->wherePivot('classe_etudiants.annee_id', $currentYear->id);
            }]);
           
            $classe= apiFindOrFail($classeQuery,   $classe_id, "no such class");
            return  $classe->etudiants;

        }
       

        // return $classe->etudiants()->wherePivot('annee_id', $currentYear->id)->get();
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
        } else {
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

    public function getClasseAttendanceRates($classe, $timestamp1, $timestamp2, $module_id = null, $currentYear_id = null)
    {
        if ($currentYear_id == null) {
            $currentYear_id = app(AnneeService::class)->getCurrentYear()->id;
        }

        $timestamp1 = ($timestamp1 !== null && !($timestamp1 instanceof Carbon)) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null && !($timestamp2 instanceof Carbon)) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;

      
        if ($timestamp1 === null && $timestamp2 === null) {
            apiSuccess([property_exists($classe, 'workedHoursSum')])->send();
            // return $classe;
            // die();
            // if (property_exists($classe, 'workedHoursSum')) {
        
             
            // } else {
             
            //     $classe->modules->sum('pivot.nbre_heure_effectue');


            // }

            $nbre_heure_effectue = $classe->workedHoursSum === null ? 0 : $classe->workedHoursSum;



            // $baseQuery = $classe->modules()->wherePivot('annee_id', $currentYear_id);

            // if ($module_id !== null) {
            //     $baseQuery = $baseQuery->where('modules.id', $module_id);
            // }

            // $nbre_heure_effectue = $this->getClasseModulesWorkedHours($baseQuery->get());

        } else if ($timestamp1 !== null && $timestamp2 !== null) {

            // $baseQuery = $classe->seances()->where('etat', seanceStateEnum::Done->value);

            // if ($module_id !== null) {
            //     $baseQuery = $baseQuery->where('module_id', $module_id);
            // }

            // $classeSeances = $baseQuery->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();
            // $nbre_heure_effectue = $this->getClasseSeancesWorkedHours($classeSeances);
        } else if ($timestamp1 !== null && $timestamp2 === null) {

            // $baseQuery = $classe->seances()->where('etat', seanceStateEnum::Done->value);

            // if ($module_id !== null) {
            //     $baseQuery = $baseQuery->where('module_id', $module_id);
            // }

            // $classeSeances = $baseQuery->where('heure_debut', '>', $timestamp1)->get();
            // $nbre_heure_effectue = $this->getClasseSeancesWorkedHours($classeSeances);
        }


        $classeStudents = $classe->etudiants;
        // $classeStudentsCount = $classeStudents->count();
        $classeStudentsCount = (int)$classe->etudiants_count;
        $strudentAttendanceRate = [];
        $classeStudentsAttendanceRate = 0;
        $nbre_heure_effectue = (int) $classe->workedHoursSum;

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
                $currentSeanceYearSegment->workedHours = ['all' => 0];
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
                $currentSeanceYearSegment->workedHours = $workedHours;
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

    public function getModuleAbsences($classe_id, $module_id, $currentYear_id, $etat = null)
    {
        // $classeModuleAbsences = Absence::whereHas('seance', function ($query) use ($seance, $currentYear) {
        //     $query->where(['module_id' => $seance->module_id, 'classe_id' => $seance->classe_id, 'annee_id' => $currentYear->id]);
        // })->where('etat', absenceStateEnum::notJustified->value)->get();
        $baseWhereClause = ['classe_id' => $classe_id, 'module_id' => $module_id, 'annee_id' => $currentYear_id];
        if ($etat !== null) {
            $baseWhereClause['etat'] = $etat;
        }
        return $absences = Absence::where($baseWhereClause)->get();
    }

    public function loadWithWorkedHoursAndStudentsMissingHoursSum($classe_id, $currentYear_id, $module_id = null, $timestamp1 = null, $timestamp2 = null)
    {
     


        $classe = $classe_id;
        if (is_numeric($classe_id)) {
            apiSuccess('test')->send();
            $classe = new Classe();
        }


        $baseQuery =   $classe->withCount([
            'etudiants' => function ($query) use ($currentYear_id, $module_id) {
                $query->where('classe_etudiants.annee_id', $currentYear_id);
            }
        ])->with(['etudiants' => function ($query) use ($currentYear_id, $module_id, $timestamp2, $timestamp1) {
            $query->wherePivot('classe_etudiants.annee_id', $currentYear_id);
            $query->withSum(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear_id, $module_id, $timestamp2, $timestamp1) {

                if ($module_id !== null) {
                    $query->where('module_id', $module_id);
                }
                if ($timestamp1 === null && $timestamp2 === null) {
                    $query->where('annee_id', $currentYear_id);
                }
                if ($timestamp1 !== null && $timestamp2 === null) {
                    $query->where('seance_heure_debut', '>', $timestamp1);
                }

                if ($timestamp1 !== null && $timestamp2 !== null) {
                    $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
                };
            }], 'duree');
        }]);

        if ($timestamp1 === null && $timestamp2 === null) {


            // $classe = Classe::withCount(['etudiants' => function ($query) use ($currentYear, $module_id) {
            //         $query->where('classe_etudiants.annee_id', $currentYear->id);
            //     }
            // ])
            // ->with(['etudiants' => function ($query) use ($currentYear, $module_id) {
            //     $query->wherePivot('classe_etudiants.annee_id', $currentYear->id);
            //     $query->withSum(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear, $module_id) {
            //         $query->where('annee_id', $currentYear->id);
            //         if ($module_id !== null) {
            //             $query->where('module_id', $module_id);
            //         }
            //     }], 'duree');
            // }])

            $classe = $baseQuery->withSum(['modules as workedHoursSum' => function ($query) use ($currentYear_id, $module_id) {
                $query->where('annee_id', $currentYear_id);
                if ($module_id !== null) {
                    $query->where('module_id', $module_id);
                }
            }], 'classe_module.nbre_heure_effectue');
        } else {



            // with(['etudiants.etudiantAbsences'=>function($query) use($currentYear,$module_id,$timestamp2,$timestamp1)  {


            //     if($module_id!==null){
            //         $query->where('module_id',$module_id);
            //     }

            //     if ($timestamp1 !== null && $timestamp2 === null) {
            //         $query->where('seance_heure_debut', '>', $timestamp1);

            //         }

            //     if ($timestamp1 !== null && $timestamp2 !== null) {
            //              $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
            //     };


            // }])


            $classe = $baseQuery->withSum(['seances as workedHoursSum' => function ($query) use ($module_id, $timestamp2, $timestamp1) {

                $baseWhereClause = ['etat' => seanceStateEnum::Done->value];



                if ($module_id !== null) {
                    $baseWhereClause['module_id'] = $module_id;
                }


                $query->where($baseWhereClause);

                if ($timestamp1 !== null && $timestamp2 === null) {
                    $query->where('heure_debut', '>', $timestamp1);
                }

                if ($timestamp1 !== null && $timestamp2 !== null) {
                    $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                };
            }], 'duree')
                // ->with('seances',function($query)use($module_id,$currentYear,$timestamp2,$timestamp1){

                //     $baseWhereClause=['etat'=> seanceStateEnum::Done->value];



                //     if($module_id!==null){
                //         $baseWhereClause['module_id']=$module_id;
                //     }


                //     $query->where($baseWhereClause);

                //     if ($timestamp1 !== null && $timestamp2 === null) {
                //         $query->where('heure_debut', '>', $timestamp1);

                //         }

                //         if ($timestamp1 !== null && $timestamp2 !== null) {
                //              $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                //         };

                // })

            ;
        }



        if (!is_numeric($classe_id)) {
           return $classe;
        }

     
        return $classe = apiFindOrFail($classe, $classe_id, "no such class");
    }
}
