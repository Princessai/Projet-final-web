<?php
function seanceDuration($seance_end,$seance_start) {
    $duration= ceil($seance_end->diffInHours($seance_start ,absolute:true));
     return $duration;
 }