<?php

namespace App\Services;

use Carbon\Carbon;
use App\Enums\attendanceStateEnum;

include_once(base_path('utilities\copyCollection.php'));
include_once(base_path('utilities\seeder\seanceDuration.php'));



class SeanceService
{
    public function getStudentAttendanceStatus($user_id , $absences, $delays){
        $attendanceStatus = attendanceStateEnum::Present->value;

        $isAbsente = $absences->contains(function ($absence,  $key) use($user_id) {
            return $absence->user_id == $user_id;
        });

        if ($isAbsente) {
            return $attendanceStatus = attendanceStateEnum::Absent->value;
        }

        $isLate = $delays->contains(function ($absence,  $key) use($user_id) {

            return $absence->user_id == $user_id;
        });

        if ($isLate) {
            return $attendanceStatus = attendanceStateEnum::Late->value;
        }

        return $attendanceStatus;
    }
}