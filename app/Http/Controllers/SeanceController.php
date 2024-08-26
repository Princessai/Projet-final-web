<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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

require(base_path('utilities\seeder\seanceDuration.php'));

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

            // 'absences' => 'present|array',
            // 'absences.*' => 'nullable|array',
            // 'absences.*.id' => 'required|integer',
            // 'absences.*.isDropped' => 'required|boolean',
            // 'delays' => 'present|array',
            // 'delays.*' => 'nullable|array',
            // 'delays.*.id' => 'required|integer',
            // 'delays.*.isDropped' => 'required|boolean',
        ]);



        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }


        $validated = $validator->validated();

        $currentYear = (new AnneeService())->getCurrentYear();

        $seance = Seance::with('classe');



        $seance = apiFindOrFail($seance, $seance_id, 'no such seance');

        $seanceStart = Carbon::parse($seance->heure_debut);

        $seanceEnd = Carbon::parse($seance->heure_fin);

        $seanceDuration = seanceDuration($seanceEnd, $seanceStart);

        // if ($seanceStart->greaterThan(now())) {
        //     return apiError(message: 'the session has not started yet');
        // }
        if ($seance->attendance) {
            return apiError(message: 'the call is already done');
        }

        // $workedHours =$seance
        // ->classe
        // ->modules()
        // ->wherePivot('annee_id',$currentYear->id)
        // ->wherePivot('module_id',$seance->module_id)
        // ->first()
        // ->pivot
        // ->nbre_heure_effectue;


        $pivotDataBaseQuery = DB::table('classe_module')
            ->where([
                'annee_id' => $currentYear->id,
                'module_id' => $seance->module_id,
                'classe_id' => $seance->classe->id
            ]);

        $pivotData = $pivotDataBaseQuery->first();


        $workedHours = $pivotData->nbre_heure_effectue;



        CourseHour::where(['classe_module_id' => $pivotData->id, 'type_seance_id' => $seance->type_seance_id])->increment('nbre_heure_effectue', $seanceDuration);

        $pivotDataBaseQuery->increment('nbre_heure_effectue', $seanceDuration);

        $totalModuleHours = $pivotData->nbre_heure_total;

        $workedHoursPercentage = round((100 * $workedHours) / $totalModuleHours, 2);

        $minWorkedHoursPercentage = 30;

        $minAttendancePercentage = 30;



        // $absences = $validated['absences'];
        // $delays = $validated['delays'];
        $absences = [];
        $delays = [];


        // $absences = collect($absences)->map(function ($data) use ($seance, $currentYear, $workedHoursPercentage, $minWorkedHoursPercentage, $workedHours, $minAttendancePercentage) {
        //     $student_id = $data['id'];
        //     $isDropped = $data['isDropped'];
        //     if ($workedHoursPercentage > $minWorkedHoursPercentage && !$isDropped) {

        //         $studentModuleAbscences = Absence::whereHas('seance', function ($query) use ($seance,) {

        //             $query = $query->where('module_id', $seance->module_id);
        //         })->where([
        //             'annee_id' => $currentYear->id,
        //             'user_id' => $student_id,
        //             'etat' => absenceStateEnum::notJustified->value
        //         ])->get();

        //         $StudentService = new StudentService;

        //         $ClasseService = new ClasseService;

        //         $missedHours =  $ClasseService->getStudentMissedHours($studentModuleAbscences);

        //         $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

        //         if ($unJustifiedpresencePercentage <= $minAttendancePercentage) {

        //             Droppe::create([
        //                 "module_id" => $seance->module_id,
        //                 "user_id" => $student_id,
        //                 "annee_id" => $currentYear->id,
        //             ]);
        //         }



        //     }





        //     return ['user_id' => $student_id, "seance_id" => $seance->id, "annee_id" => $currentYear->id];
        // });
        // $seance->absences()->createMany($absences);

        // $delays = collect($delays)->map(function ($data) use ($seance, $currentYear) {
        //     $student_id = $data['id'];

        //     return ['user_id' => $student_id, "seance_id" => $seance->id, "annee_id" => $currentYear->id];
        // });


        foreach ($validated['attendances'] as $data) {


            $student_id = $data['id'];
            $isDropped = $data['isDropped'];
            $attendanceStatus = $data['status'];
            $attendanceTabName = null;


            // $isAbsent = ($attendanceStatus === attendanceStateEnum::Absent->value) ? true : false;


            if ($attendanceStatus === attendanceStateEnum::Absent->value && $workedHoursPercentage > $minWorkedHoursPercentage && !$isDropped) {

                $studentModuleAbscences = Absence::whereHas('seance', function ($query) use ($seance,) {

                    $query = $query->where('module_id', $seance->module_id);
                })->where([
                    'annee_id' => $currentYear->id,
                    'user_id' => $student_id,
                    'etat' => absenceStateEnum::notJustified->value
                ])->get();

                $StudentService = new StudentService;

                $ClasseService = new ClasseService;

                $missedHours =  $ClasseService->getStudentMissedHours($studentModuleAbscences);

                $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

                if ($unJustifiedpresencePercentage <= $minAttendancePercentage) {

                    Droppe::create([
                        "module_id" => $seance->module_id,
                        "user_id" => $student_id,
                        "annee_id" => $currentYear->id,
                    ]);
                }
            }

            if ($attendanceStatus === attendanceStateEnum::Absent->value) {
                $attendanceTabName = 'absences';
            }
            if ($attendanceStatus === attendanceStateEnum::Late->value) {
                $attendanceTabName = 'delays';
            }

            if ($attendanceTabName !== null) {
                $$attendanceTabName[] = ['user_id' => $student_id, "seance_id" => $seance->id, "annee_id" => $currentYear->id];
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

        $seance = Seance::with([
            'classe.etudiants',
            'absences',
            'delays'

        ]);
        $seance = apiFindOrFail($seance, $seance_id, message: 'no such session');

        $ClasseService = new ClasseService;
        $AnneeService = new AnneeService();
        $classe = $seance->classe;
        $currentYear = $AnneeService->getCurrentYear();
        $students =  $ClasseService->getClassCurrentStudent($classe, $currentYear);

        $response = (new UserCollection($students))
            ->setCurrentYear($currentYear)
            ->setSeance($seance);

        return apiSuccess(data: $response);
    }

    public function updateClasseCall(AttendanceRecordsRequest $request, $seance_id)
    {

        $seance = Seance::with([
            'classe',
            'absences',
            'delays'
        ]);
        $seance = apiFindOrFail($seance, $seance_id, message: 'no such session');

        $ClasseService = new ClasseService();
        $currentYear = (new AnneeService())->getCurrentYear();
        $StudentService = new StudentService();
        $minAttendancePercentage = 30;

        $validated = $request->validated();
        $seanceAbsences =  $seance->absences;
        $seanceDelays =  $seance->delays;

        $classeModuleAbsences = Absence::whereHas('seance', function ($query) use ($seance, $currentYear) {
            $query->where(['module_id' => $seance->module_id, 'classe_id' => $seance->classe_id, 'annee_id' => $currentYear->id]);
        })->where('etat', absenceStateEnum::notJustified->value)->get();

        // $classesModules = $seance->classe
        //     ->modules()
        //     ->wherePivot('annee_id', $currentYear->id)
        //     ->where('modules.id', $seance->module_id)
        //     ->first();

        // $workedHours  =  $ClasseService->getClasseModulesWorkedHours($classesModules);

        $pivotDataBaseQuery = DB::table('classe_module')
            ->where([
                'annee_id' => $currentYear->id,
                'module_id' => $seance->module_id,
                'classe_id' => $seance->classe->id
            ]);

        $pivotData = $pivotDataBaseQuery->first();


        $workedHours = $pivotData->nbre_heure_effectue;


        $SeanceService = new SeanceService();


        // $absences = $validated['absences'];
        // $delays = $validated['delays'];
        // $presences = $validated['attendances'];


        foreach ($validated['attendances'] as $data) {


            $student_id = $data['id'];
            $PrevAttendanceStatus = $SeanceService->getStudentAttendanceStatus($student_id, $seanceAbsences, $seanceDelays);
            $currentAttendanceStatus = $data['status'];

            if ($currentAttendanceStatus == attendanceStateEnum::Present->value) {


                if ($PrevAttendanceStatus == attendanceStateEnum::Late->value) {
                    Retard::where([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYear->id
                    ])->delete();
                }
            }


            if ($currentAttendanceStatus == attendanceStateEnum::Late->value) {
                // if ($PrevAttendanceStatus == attendanceStateEnum::Absent->value) {
                //     Absence::where(['user_id' => $student_id, 
                //     'seance_id' => $seance->id, 
                //     'annee_id' => $currentYear->id])->delete();
                // }

                if ($PrevAttendanceStatus != attendanceStateEnum::Late->value) {

                    Retard::create([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYear->id,
                    ]);
                }
            }

            if ($currentAttendanceStatus == attendanceStateEnum::Absent->value) {
                if ($PrevAttendanceStatus == attendanceStateEnum::Late->value) {
                    Retard::where([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYear->id
                    ])->delete();
                }

                if ($PrevAttendanceStatus != attendanceStateEnum::Absent->value) {
                    Absence::create([
                        'user_id' => $student_id,
                        'seance_id' => $seance->id,
                        'annee_id' => $currentYear->id,
                    ]);
                }
            }


            if (
                $currentAttendanceStatus != attendanceStateEnum::Absent->value &&
                $PrevAttendanceStatus == attendanceStateEnum::Absent->value
            ) {
                Absence::where(['user_id' => $student_id, 'seance_id' => $seance->id, 'annee_id' => $currentYear->id])->delete();

                if ($data['isDropped']) {

                    $studentAbsences = $classeModuleAbsences->filter(function ($absence) use ($student_id, $seance) {
                        $absence->user_id == $student_id && $absence->seance_id != $seance->id;
                    });

                    $missedHours =  $ClasseService->getStudentMissedHours($studentAbsences);

                    $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

                    if ($unJustifiedpresencePercentage > $minAttendancePercentage) {
                        Droppe::where([
                            'user_id' => $student_id,
                            'module_id' => $seance->module_id,
                            'annee_id' => $currentYear->id
                        ])->delete();
                    }
                }
            }
        }

        return apiSuccess(message: "attendance modified successfully");
    }
}
