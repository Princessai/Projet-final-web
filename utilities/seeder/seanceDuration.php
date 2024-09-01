<?php

use Carbon\Carbon;
function seanceDuration($seance_end,$seance_start,$ceil=true) {
    if(!$seance_end instanceof Carbon){
        $seance_end = Carbon::parse($seance_end);
    }

    if(!$seance_start instanceof Carbon){
        $seance_start= Carbon::parse($seance_start);
    }
    $duration =$seance_end->diffInHours($seance_start ,absolute:true);
    if($ceil===false){
        return $duration;
    }
   
     return  ceil($duration);;
 }