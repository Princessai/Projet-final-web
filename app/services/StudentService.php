<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use Carbon\Callback;
use App\Models\Droppe;
use App\Models\Absence;
use App\Services\AnneeService;
use App\Enums\absenceStateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoWorkedHoursException;
use Illuminate\Database\Eloquent\Collection;

class StudentService
{

    public function AttendancePercentageCalc($missedHours, $workedHours)

    {
        if($workedHours == 0){
            throw new NoWorkedHoursException('division by zero no  courses have been done.');
        }

        $absencePercentage = ($missedHours * 100) / $workedHours;
        
        $attendanceRate = round(100 - $absencePercentage, 2);

        return $attendanceRate;
    }
    public function  calcAttendanceMark($attendancePercentage,$maxAttendanceMark){

        $attendanceMark =round(($attendancePercentage * 20) / 100, 2);
        if($maxAttendanceMark === null) return $attendanceMark;
        return min($maxAttendanceMark, $attendanceMark);
}
    public function getCurrentClasse($user, $currentYear=null)
    {
        // if($user instanceof Builder|| $user instanceof Model){
         
        //    return $user->with('etudiantsClasses', function ($query)use($currentYear){

        //     if( $currentYear===null){

        //         $currentYear =(new AnneeService)->getCurrentYear();
        //         $query->wherePivot('annee_id',$currentYear->id);

        //     }

        //     });
        // }
        
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

        return (int)$absencebaseQuery->sum('duree');
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

    public function updateOrInsertDroppedStudents($module_id, $student_id, $currentYearId, $classe_id, $seanceStart){

        $droppeBaseAttributes =[
            "module_id" => $module_id,
            "user_id" => $student_id,
            "annee_id" =>  $currentYearId,
            "classe_id" =>  $classe_id,

        ];

        $droppeBaseQuery = Droppe::where($droppeBaseAttributes);

        $beenDropped =$droppeBaseQuery->exists();

        if($beenDropped){
            $droppeBaseQuery->update(['updated_at'=>$seanceStart,'isDropped' => true ]);

        }
        else{
      

            $droppeBaseAttributes['isDropped']=true;
            $droppeBaseAttributes['created_at']=$seanceStart;
            $droppeBaseAttributes['updated_at']=$seanceStart;

            Droppe::create($droppeBaseAttributes);
        }

    }

    public function loadStudentmissedHoursSum($query, $module_id = null,$currentYear_id = null,$timestamp1 = null,
    $timestamp2 = null,
    $callback = null,string $loading='with'){
        $loading.="Sum";
           
        $baseQuery =   $query->$loading(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear_id, $module_id, $timestamp2, $timestamp1, $callback) {

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

            if ($callback !== null) {
                $callback($query);
            }
        }], 'duree');

    }
}
