<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Annee;
use App\Models\Classe;
use App\Models\Absence;
use Illuminate\Http\Request;
use App\Enums\seanceStateEnum;
use App\Http\Resources\ClasseAttendanceRateResource;
use App\Services\ClasseService;
use App\Http\Resources\ClasseStudentsAbsencesResource;
use App\Services\StudentService;

require(base_path('utilities\seeder\seanceDuration.php'));


class AbsenceController extends Controller
{
    public function getStudentAttendanceRate($student_id, $timestamp1 = null, $timestamp2 = null)
    {

        $currentYear = Annee::latest()->first();
        if ($timestamp1 === null && $timestamp2 === null) {

            $student = User::with(['etudiantsClasses.modules', 'etudiantAbsences.seance']);
        } else {
            $student = User::with(['etudiantsClasses.seances.absences']);
        }
        $classeService = new ClasseService;

        $student = apiFindOrFail($student, $student_id, "no such student");

        // try {
        //     $student = $student->findOrFail($student_id);
        // } catch (\Throwable $th) {
        //     return apiError(message: "no such student");
        // }

        $studentClasse = $student->etudiantsClasses()->wherePivot('annee_id', $currentYear->id)->first();

        $missingHoursCount = 0;

        $nbre_heure_effectue = 0;

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;

        if ($timestamp1 === null && $timestamp2 === null) {

            $studentAbsences = $student->etudiantAbsences()->where(['annee_id' => $currentYear->id])->get();


            // foreach ($studentAbsences as $studentAbsence) {
            //     $seance = $studentAbsence->seance;
            //     $startHour =  Carbon::parse($seance->heure_debut);
            //     $endHour =  Carbon::parse($seance->heure_fin);

            //     $duree = seanceDuration($endHour, $startHour);


            //     $missingHoursCount += $duree;
            // }


            $missingHoursCount = $classeService->getStudentMissedHours($studentAbsences);

            $classesModules = $studentClasse->modules;
            // foreach ($classesModules as $classesModule) {

            //     $nbre_heure_effectue += $classesModule->pivot->nbre_heure_effectue;
            // }

            $nbre_heure_effectue =  $classeService->getClasseModulesWorkedHours($classesModules);
        }


        if ($timestamp1 !== null && $timestamp2 !== null) {

            // $studentAbsences = Absence::with(['seance'])->whereBetween();

            // $timestamp1 = Carbon::createFromTimestamp($timestamp1)->toDateTimeString();
            // $timestamp2 = Carbon::createFromTimestamp($timestamp2)->toDateTimeString();

            $seancesClasses = $studentClasse->seances()->where('etat', seanceStateEnum::Done->value)->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();
            // $seancesClasses = $studentClasse->seances()->where('heure_debut','>' ,$timestamp1);

            // foreach ($seancesClasses as $seance) {
            //     $startHour =  Carbon::parse($seance->heure_debut);
            //     $endHour =  Carbon::parse($seance->heure_fin);
            //     $duree = seanceDuration($endHour, $startHour);
            //     $nbre_heure_effectue += $duree;

            //     $studentAbsence = $seance->absences()->where('user_id', $student_id)->first();

            //     if (!is_null($studentAbsence)) {
            //         //  return apiSuccess(data: $studentAbsence);
            //         $missingHoursCount += $duree;
            //     }
            // }

            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $classeService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);

            // return apiSuccess(data: $missingHoursCount);
        }


        if ($timestamp1 !== null && $timestamp2 === null) {

            $seancesClasses = $studentClasse->seances()->where('etat', seanceStateEnum::Done->value)->where('heure_debut', '>', $timestamp1)->get();

            // return apiSuccess(data: $seancesClasses);

            // foreach ($seancesClasses as $seance) {
            //     $startHour =  Carbon::parse($seance->heure_debut);
            //     $endHour =  Carbon::parse($seance->heure_fin);
            //     $duree = seanceDuration($endHour, $startHour);
            //     $nbre_heure_effectue += $duree;

            //     $studentAbsence = $seance->absences()->where('user_id', $student_id)->first();

            //     if (!is_null($studentAbsence)) {
            //         //  return apiSuccess(data: $studentAbsence);
            //         $missingHoursCount += $duree;
            //     }
            // }

            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $classeService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        }


        // return apiSuccess(data: [$nbre_heure_effectue, $seancesClasses ]);

        // if ($nbre_heure_effectue != 0) {
        //     //  return apiSuccess(data: $studentAbsence);
        //     $absencePercentage = ($missingHoursCount * 100) / $nbre_heure_effectue;
        // } else {
        //     return apiError(message: 'no courses has been done for this interval');
        // }

        $studentService = new StudentService;

        $presencePercentage = $studentService->percentageCalc($missingHoursCount, $nbre_heure_effectue);

        $maxAttendanceMark = 15;

        $attendanceMark = round(($presencePercentage * 20) / 100, 2);
        $attendanceMark = min($maxAttendanceMark, $attendanceMark);
        $response = ["hours worked" => $nbre_heure_effectue, 'missing hours' => $missingHoursCount, 'presence percentage' => $presencePercentage, 'attendance mark' => $attendanceMark];
        return apiSuccess(data: $response);
    }

    public function getClassseStudentsAttendanceRate($classe_id, $timestamp1 = null, $timestamp2 = null)
    {
        $classeService = new ClasseService;

        if ($timestamp1 === null && $timestamp2 === null) {
            $classe = Classe::with(['etudiants.etudiantAbsences', 'modules']);
        } else {
            $classe = Classe::with(['etudiants.etudiantAbsences', 'seances']);
        }

        $classe = apiFindOrFail($classe, $classe_id, "no such class");

        // $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        // $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;


        // if ($timestamp1 === null && $timestamp2 === null) {

        //     $nbre_heure_effectue = $classeService->getClasseModulesWorkedHours($classe->modules);
        // } else if ($timestamp1 !== null && $timestamp2 !== null) {

        //     $classeSeances = $classe->seances()->where('etat', seanceStateEnum::Done->value)->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();
        //     $nbre_heure_effectue = $classeService->getClasseSeancesWorkedHours($classeSeances);
        // } else if ($timestamp1 !== null && $timestamp2 === null) {

        //     $classeSeances = $classe->seances()->where('etat', seanceStateEnum::Done->value)->where('heure_debut', '>', $timestamp1)->get();
        //     $nbre_heure_effectue = $classeService->getClasseSeancesWorkedHours($classeSeances);
        // }


        // $classeStudents = $classe->etudiants;
        // $classeStudentsCount = $classeStudents->count();
        // $response = [];
        // $classeStudentsAttendanceRate = 0;
        // foreach ($classeStudents as $classeStudent) {

        //     // return apiSuccess(data:new ClasseStudentsAbsencesResource($classeStudent, $nbre_heure_effectue));
        //     $apiResource = new ClasseStudentsAbsencesResource($classeStudent, $nbre_heure_effectue);


        //     $attendanceRate = $apiResource->toArray(request())['attendanceRate'];
        //     //  return apiSuccess( $attendanceRate );
        //     $classeStudentsAttendanceRate += $attendanceRate;

        //     $response[] =  $apiResource;
        // }

        // $classeAttendanceRate = round($classeStudentsAttendanceRate / $classeStudentsCount, 2);

        ['strudentAttendanceRate' => $strudentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate] = $classeService->getClasseAttendanceRates($classe, $timestamp1, $timestamp2);

        return apiSuccess(data: ['strudentAttendanceRate' => $strudentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate]);
    }

    public function getClasssesAttendanceRate()
    {
        $classes = Classe::all();
        $response =  ClasseAttendanceRateResource::collection($classes);
        return apiSuccess(data: $response);
    }
}
