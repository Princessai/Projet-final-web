<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\CourseHour;
use InvalidArgumentException;
use App\Services\ClasseService;
use App\Enums\attendanceStateEnum;
use Illuminate\Support\Facades\DB;

include_once(base_path('utilities\copyCollection.php'));
include_once(base_path('utilities\seeder\seanceDuration.php'));



class SeanceService
{
    public function getStudentAttendanceStatus($user_id, $absences, $delays)
    {
        $attendanceStatus = attendanceStateEnum::Present->value;

        $isAbsente = $absences->contains(function ($absence,  $key) use ($user_id) {
            return $absence->user_id == $user_id;
        });

        if ($isAbsente) {
            return $attendanceStatus = attendanceStateEnum::Absent->value;
        }

        $isLate = $delays->contains(function ($absence,  $key) use ($user_id) {

            return $absence->user_id == $user_id;
        });

        if ($isLate) {
            return $attendanceStatus = attendanceStateEnum::Late->value;
        }

        return $attendanceStatus;
    }

    public function incrementOrDecrementWorkedHours($seance, $currentYearId, int $action = 1)
    {
        if ($action !== -1 && $action !== 1) {
            throw new InvalidArgumentException("Parameter must be -1 or 1.");
        }

        $ClasseService = new ClasseService;

        // $pivotDataBaseQuery = DB::table('classe_module')
        //     ->where([
        //         'annee_id' => $currentYearId,
        //         'module_id' => $seance->module_id,
        //         'classe_id' => $seance->classe->id
        //     ]);

        $pivotDataBaseQuery  = $ClasseService->getClasseModuleQuery($currentYearId, $seance->module_id, $seance->classe->id);
        $pivotData = $pivotDataBaseQuery->first();


        $seanceDuration = $seance->duree;



        $isThereModuleCourseHours = CourseHour::where(['classe_module_id' => $pivotData->id, 'type_seance_id' => $seance->type_seance_id])->exists();

        if (!$isThereModuleCourseHours) {
            if ($action === -1) $seanceDuration = 0;
            CourseHour::create([
                'classe_module_id' => $pivotData->id,
                'type_seance_id' => $seance->type_seance_id,
                'nbre_heure_effectue' => $seanceDuration,
            ]);
        } else {
            $coursHoursBaseQuery = CourseHour::where(['classe_module_id' => $pivotData->id, 'type_seance_id' => $seance->type_seance_id]);
            if ($action === 1) {
                $coursHoursBaseQuery->increment('nbre_heure_effectue', $seanceDuration);
            } else if ($action === -1) {
                $coursHoursBaseQuery->decrement('nbre_heure_effectue', $seanceDuration);
            }
        }

        if ($action === 1) {
            $pivotDataBaseQuery->increment('nbre_heure_effectue', $seanceDuration);
        } else if ($action === -1) {
            $pivotDataBaseQuery->decrement('nbre_heure_effectue', $seanceDuration);
        }
        $pivotData->nbre_heure_effectue+=$seanceDuration;
        return   $pivotData ;
    }
}
