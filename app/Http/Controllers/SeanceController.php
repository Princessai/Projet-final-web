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
        // return apiSuccess($seance);
        if ($seance->attendance) {
            return apiError(message: 'the call is already done');
        }



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

                $student = User::active(module_id: $seance->module_id, currentYear_id: $currentYearId)->find($student_id);

                // return $student;

                // $missedHours =  $StudentService->getMissedHours(student_id: $student_id, currentYear_id:  $currentYearId, module_id: $seance->module_id, callback: function ($query) {
                //     $query->where('etat', absenceStateEnum::notJustified->value);
                // });

                $missedHours = $student->missedHoursSum;
                $workedHours = $student->etudiantsClasses->first()->workedHoursSum;
                $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

                if ($unJustifiedpresencePercentage <= $minAttendancePercentage) {

                    Droppe::create([
                        "module_id" => $seance->module_id,
                        "user_id" => $student_id,
                        "annee_id" =>  $currentYearId,
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

        $seance = Seance::with([
            'classe.etudiants',
            'absences',
            'delays'

        ]);
        $seance = apiFindOrFail($seance, $seance_id, message: 'no such session');

        $ClasseService = new ClasseService;
        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        $classe = $seance->classe;
        $students =  $ClasseService->getClassCurrentStudent($classe, $currentYearId);

        $response = (new UserCollection($students))
            ->setCurrentYear($currentYearId)
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

        // $classeModuleAbsences = $ClasseService->getModuleAbsences(classe_id: $seance->classe_id, module_id: $seance->module_id, currentYear_id: $currentYear->id, etat: absenceStateEnum::notJustified->value);

        $heure_fin = Carbon::parse($seance->heure_fin);
        $offSet = now()->subDays(7);
        if ($heure_fin->lessThanOrEqualTo($offSet)) {
            return apiError(message: 'oops ! modification deadline has passed');
        }


        $pivotDataBaseQuery = DB::table('classe_module')
            ->where([
                'annee_id' => $currentYear->id,
                'module_id' => $seance->module_id,
                'classe_id' => $seance->classe->id
            ]);

        $pivotData = $pivotDataBaseQuery->first();


        $workedHours = $pivotData->nbre_heure_effectue;


        $SeanceService = new SeanceService();




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
                        'annee_id' => $currentYear->id
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
                        'annee_id' => $currentYear->id,
                        'module_id' => $seance->module_id,

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

                    $missedHours =  $StudentService->calcMissedHours($studentAbsences);

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
