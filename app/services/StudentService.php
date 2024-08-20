<?php
namespace App\Services;

use Carbon\Carbon;


class StudentService{

    public function percentageCalc ($missingHours, $nbre_heure_effectue) {
        if ($nbre_heure_effectue !=0) {
            $absencePercentage = ($missingHours *100) /$nbre_heure_effectue;           
        } else {
             apiError(message: 'no courses has been done for this interval')->send();
            die();
        }
      

       
        $presencePercentage = round(100 - $absencePercentage, 2);

        return $presencePercentage;
    }
}
