<?php
namespace App\Services;

use Carbon\Carbon;


class StudentService{

    public function AttendancePercentageCalc ($missedHours, $workedHours) {
        if ($workedHours !=0) {
            $absencePercentage = ($missedHours *100) /$workedHours;           
        } else {
             apiError(message: 'no courses has been done for this interval')->send();
            die();
        }
       
        $attendanceRate = round(100 - $absencePercentage, 2);

        return $attendanceRate;
    }

    public function getCurrentClasse($user,$currentYear){
        $studentClasses =$this->getCurrentClasses($user,$currentYear);
        return $studentClasses->last();
    }


    public function getCurrentClasses($user,$currentYear){
       return  $studentClasses =$user->etudiantsClasses()->wherePivot('annee_id', $currentYear->id)->get();
      
    }


}
