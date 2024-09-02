<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Droppe;
use App\Models\Retard;
use App\Models\Seance;
use App\Models\Absence;
use App\Models\CourseHour;
use Illuminate\Http\Request;
use App\Enums\seanceStateEnum;
use App\Services\AnneeService;
use App\Enums\absenceStateEnum;
use App\Services\ClasseService;
use App\Services\SeanceService;
use Illuminate\Validation\Rule;
use App\Services\StudentService;
use App\Enums\attendanceStateEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\AttendanceRecordsRequest;

include_once(base_path('utilities\seeder\seanceDuration.php'));

class SeanceController extends Controller
{
    public function makeClasseCall(Request $request, $seance_id)
    {


        $validator = Validator::make($request->all(), [

            'attendances' => 'present|array',
            'attendances.*' => 'nullable|array',
            'attendances.*.id' => 'required|integer',
            'attendances.*.isDropped' => 'required|boolean',
            'attendances.*.status' => ['required', Rule::enum(attendanceStateEnum::class),]

        ]);



        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }


        $validated = $validator->validated();

        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;

        $SeanceService = new SeanceService;


        $seance = Seance::with('classe');



        $seance = apiFindOrFail($seance, $seance_id, 'no such seance');

        $seanceStart = Carbon::parse($seance->heure_debut);

        $seanceEnd = Carbon::parse($seance->heure_fin);



        // if ($seanceStart->greaterThan(now())) {
        //     return apiError(message: 'the session has not started yet');
        // }

        // if ($seance->attendance) {
        //     return apiError(message: 'the call is already done');
        // }



        $pivotData = $SeanceService->incrementOrDecrementWorkedHours($seance,  $currentYearId);

        $workedHours = $pivotData->nbre_heure_effectue;

        $totalModuleHours = $pivotData->nbre_heure_total;

        $workedHoursPercentage = round((100 * $workedHours) / $totalModuleHours, 2);

        $minWorkedHoursPercentage = 30;

        $minAttendancePercentage = 30;

        $absences = [];
        $delays = [];


        foreach ($validated['attendances'] as $data) {


            $student_id = $data['id'];
            $isDropped = $data['isDropped'];
            $attendanceStatus = $data['status'];
            $attendanceTabName = null;





            if ($attendanceStatus === attendanceStateEnum::Absent->value && $workedHoursPercentage > $minWorkedHoursPercentage && !$isDropped) {


                $StudentService = new StudentService;

                $student = User::eagerLoadStudentWorkedAdMissedHours(
                    module_id: $seance->module_id,
                    currentYear_id: $currentYearId,
                    callback: function ($query) {
                        $query->where('etat', absenceStateEnum::notJustified->value);
                    }
                )->find($student_id);
               


                // return $student;

                // $missedHours =  $StudentService->getMissedHours(student_id: $student_id, currentYear_id:  $currentYearId, module_id: $seance->module_id, callback: function ($query) {
                //     $query->where('etat', absenceStateEnum::notJustified->value);
                // });

                $missedHours = $student->missedHoursSum;
                $workedHours = $student->etudiantsClasses->first()->workedHoursSum;
                //  return [$missedHours,$workedHours];
                $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc((int) $missedHours, (int) $workedHours);

                // return $unJustifiedpresencePercentage;

                if ($unJustifiedpresencePercentage <= $minAttendancePercentage) {
                    // return 'test';
                    Droppe::updateOrInsert(
                        [
                            "module_id" => $seance->module_id,
                            "user_id" => $student_id,
                            "annee_id" =>  $currentYearId,
                        ],
                        ['isDropped' => true]
                    );

                    // Droppe::create([
                    //     "module_id" => $seance->module_id,
                    //     "user_id" => $student_id,
                    //     "annee_id" =>  $currentYearId,

                    // ]);
                }
            }

            if ($attendanceStatus === attendanceStateEnum::Absent->value) {
                $attendanceTabName = 'absences';
            }
            if ($attendanceStatus === attendanceStateEnum::Late->value) {
                $attendanceTabName = 'delays';
            }

            if ($attendanceTabName !== null) {

                $$attendanceTabName[] = [
                    'user_id' => $student_id,
                    'module_id' => $seance->module_id,
                    'seance_heure_fin' => $seance->heure_fin,
                    'seance_heure_debut' => $seance->heure_debut,
                    'duree' => $seance->duree,
                    'duree_raw' => $seance->duree_raw,
                    "seance_id" => $seance->id,
                    "annee_id" => $currentYearId
                ];
            }
        }

        $seance->absences()->createMany($absences);
        $seance->delays()->createMany($delays);
        $seance->etat = seanceStateEnum::Done->value;
        $seance->attendance = true;
        $seance->save();

        return apiSuccess(message: 'call successfully made');
    }

    public function editClasseCall(Request $request, $seance_id)
    {
        // 'classe.etudiants',
        // 'absences',
        // 'delays'

        $ClasseService = new ClasseService;
        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;


        $seance = Seance::with([
            'module' => function ($query) use ($currentYearId) {


                $query->with('droppedStudents', function ($query) use ($currentYearId) {
                    $query->wherePivot('annee_id', $currentYearId);
                });
            },
            'classe' => function ($query) {
                $query->CurrentYearStudents();
            },
            'absences',
            'delays'

        ]);
        $seance = apiFindOrFail($seance, $seance_id, message: 'no such session');




        $classe = $seance->classe;

        // $students =  $ClasseService->getClassCurrentStudent($classe, $currentYearId);

        $response = (new UserCollection($classe->etudiants))
            ->setCurrentYear($currentYearId)
            ->setSeance($seance);

        return apiSuccess(data: $response);
    }

    public function updateClasseCall(AttendanceRecordsRequest $request, $seance_id)
    {

        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;

        $StudentService = new StudentService();



        $seance = Seance::with(['classe']);

        $seance = apiFindOrFail($seance, $seance_id, message: 'no such session');



        $minAttendancePercentage = 30;

        $validated = $request->validated();

        $studentsAttendanceId = collect($validated['attendances'])->pluck('id');

        $studentAbsences = Absence::whereIn('user_id', $studentsAttendanceId)->where(['module_id' => $seance->module_id, 'annee_id' => $currentYearId])->get();

        $studentDelays = Retard::whereIn('user_id', $studentsAttendanceId)->where(['module_id' => $seance->module_id, 'annee_id' => $currentYearId])->get();




        $seanceAbsences = $studentAbsences->filter(function ($absence) use ($seance) {
            return $absence->seance_id == $seance->id;
        });

        $seanceDelays = $studentDelays->filter(function ($delay) use ($seance) {
            return $delay->seance_id == $seance->id;
        });



        // $classeModuleAbsences = 
        // Absence::whereHas('seance', function ($query) use ($seance, $currentYearId) {

        //     $query->where([
        //         'module_id' => $seance->module_id,
        //         'classe_id' => $seance->classe_id,
        //         'annee_id' => $currentYearId
        //     ]);
        // })->where('etat', absenceStateEnum::notJustified->value)->get();

        // $classeModuleAbsences = $ClasseService->getModuleAbsences(classe_id: $seance->classe_id, module_id: $seance->module_id, currentYear_id: $currentYearId, etat: absenceStateEnum::notJustified->value);

        $heure_fin = Carbon::parse($seance->heure_fin);
        $offSet = now()->subDays(7);
        // if ($heure_fin->lessThanOrEqualTo($offSet)) {
        //     return apiError(message: 'oops ! modification deadline has passed');
        // }


        $pivotDataBaseQuery = DB::table('classe_module')
            ->where([
                'annee_id' => $currentYearId,
                'module_id' => $seance->module_id,
                'classe_id' => $seance->classe->id
            ]);

        $pivotData = $pivotDataBaseQuery->first();


        $workedHours = $pivotData->nbre_heure_effectue;


        $SeanceService = new SeanceService();




        foreach ($validated['attendances'] as $data) {


            $student_id = $data['id'];
            $PrevAttendanceStatus = $SeanceService->getStudentAttendanceStatus($student_id, $seanceAbsences,  $seanceDelays);
            $currentAttendanceStatus = $data['status'];

            if ($currentAttendanceStatus == attendanceStateEnum::Present->value) {


                if ($PrevAttendanceStatus == attendanceStateEnum::Late->value) {
                    Retard::where([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYearId
                    ])->delete();
                }
            }


            if ($currentAttendanceStatus == attendanceStateEnum::Late->value) {
                // if ($PrevAttendanceStatus == attendanceStateEnum::Absent->value) {
                //     Absence::where(['user_id' => $student_id, 
                //     'seance_id' => $seance->id, 
                //     'annee_id' => $currentYearId])->delete();
                // }

                if ($PrevAttendanceStatus != attendanceStateEnum::Late->value) {

                    Retard::create([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYearId,
                        'duree' => $seance->duree,
                        'duree_raw' => $seance->duree_raw,
                        'seance_heure_fin' => $seance->heure_fin,
                        'seance_heure_debut' => $seance->heure_debut,
                        'module_id' => $seance->module_id,

                    ]);
                }
            }

            if ($currentAttendanceStatus == attendanceStateEnum::Absent->value) {
                if ($PrevAttendanceStatus == attendanceStateEnum::Late->value) {
                    Retard::where([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYearId
                    ])->delete();
                }

                if ($PrevAttendanceStatus != attendanceStateEnum::Absent->value) {
                    Absence::create([
                        'user_id' => $student_id,
                        'duree' => $seance->duree,
                        'duree_raw' => $seance->duree_raw,
                        'seance_heure_fin' => $seance->heure_fin,
                        'seance_heure_debut' => $seance->heure_debut,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYearId,
                        'module_id' => $seance->module_id,

                    ]);

                    $studentUnjustifiedAbsences = $studentAbsences->filter(function ($absence) use ($student_id, $seance) {
                        $absence->user_id == $student_id && $absence->seance_id != $seance->id && $absence->etat == absenceStateEnum::notJustified->value;
                    });

                    $missedHours =  $StudentService->calcMissedHours($studentUnjustifiedAbsences);

                    $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);
                }
            }


            if (
                $currentAttendanceStatus != attendanceStateEnum::Absent->value &&
                $PrevAttendanceStatus == attendanceStateEnum::Absent->value
            ) {
                Absence::where(['user_id' => $student_id, 'seance_id' => $seance->id, 'annee_id' => $currentYearId])->delete();

                if ($data['isDropped']) {

                    $studentUnjustifiedAbsences = $studentAbsences->filter(function ($absence) use ($student_id, $seance) {
                        $absence->user_id == $student_id && $absence->seance_id != $seance->id && $absence->etat == absenceStateEnum::notJustified->value;
                    });

                    $missedHours =  $StudentService->calcMissedHours($studentUnjustifiedAbsences);

                    $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

                    if ($unJustifiedpresencePercentage > $minAttendancePercentage) {
                        Droppe::where([
                            'user_id' => $student_id,
                            'module_id' => $seance->module_id,
                            'annee_id' => $currentYearId
                        ])->update(['isDropped' => false]);
                    }
                }
            }
        }

        return apiSuccess(message: "attendance modified successfully");
    }
}
