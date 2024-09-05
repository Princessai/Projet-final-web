<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Annee;
use App\Models\Classe;
use App\Models\Droppe;
use App\Models\Absence;
use App\Models\YearSegment;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\seanceStateEnum;
use App\Services\AnneeService;
use App\Enums\absenceStateEnum;
use App\Services\ClasseService;
use App\Services\StudentService;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\AbsenceResource;
use App\Http\Resources\AbsenceCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\YearSegmentAbsenceResource;
use App\Http\Resources\ClasseAttendanceRateResource;

include_once(base_path('utilities\seeder\seanceDuration.php'));


class AbsenceController extends Controller
{
    public function getStudentAttendanceRate($student_id, $timestamp1 = null, $timestamp2 = null)
    {


        $currentYear = (new AnneeService)->getCurrentYear();
        $StudentService = new StudentService;
        $ClasseService = new ClasseService;
        if ($timestamp1 === null && $timestamp2 === null) {

            $student = User::with(['etudiantsClasses.modules']);
            $student = $StudentService->getMissedHours($student, $currentYear->id);
        } else {
            $student = User::with(['etudiantsClasses.seances.absences']);
        }



        $student = apiFindOrFail($student, $student_id, "no such student");






        $studentClasse = $student->etudiantsClasses()->wherePivot('annee_id', $currentYear->id)->first();


        $missingHoursCount = 0;

        $nbre_heure_effectue = 0;

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;

        if ($timestamp1 === null && $timestamp2 === null) {

            // $studentAbsences = $student->etudiantAbsences()->where(['annee_id' => $currentYear->id])->get();
            // $missingHoursCount = $ClasseService->getStudentMissedHours($studentAbsences);


            $missingHoursCount = (int) $student->missingHoursSum;


            $classesModules = $studentClasse->modules()->wherePivot('annee_id', $currentYear->id)->get();
            // foreach ($classesModules as $classesModule) {

            //     $nbre_heure_effectue += $classesModule->pivot->nbre_heure_effectue;
            // }

            $nbre_heure_effectue =  $ClasseService->getClasseModulesWorkedHours($classesModules);
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

            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);

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

            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        }


        // return apiSuccess(data: [$nbre_heure_effectue, $seancesClasses ]);

        // if ($nbre_heure_effectue != 0) {
        //     //  return apiSuccess(data: $studentAbsence);
        //     $absencePercentage = ($missingHoursCount * 100) / $nbre_heure_effectue;
        // } else {
        //     return apiError(message: 'no courses has been done for this interval');
        // }



        $presencePercentage = $StudentService->AttendancePercentageCalc($missingHoursCount, $nbre_heure_effectue);

        $maxAttendanceMark = 15;

        $attendanceMark = round(($presencePercentage * 20) / 100, 2);
        $attendanceMark = min($maxAttendanceMark, $attendanceMark);
        $response = ["hours worked" => $nbre_heure_effectue, 'missing hours' => $missingHoursCount, 'presence percentage' => $presencePercentage, 'attendance mark' => $attendanceMark];
        return apiSuccess(data: $response);
    }

    public function getClassseStudentsAttendanceRate($classe_id, $timestamp1 = null, $timestamp2 = null)
    {
        $ClasseService = new ClasseService;
        $currentYear = app(AnneeService::class)->getCurrentYear();

        // if ($timestamp1 === null && $timestamp2 === null) {
        //     // $classe = Classe::with(['etudiants.etudiantAbsences', 'modules']);

        // } else {
        //     $classe = Classe::with(['etudiants.etudiantAbsences', 'seances']);
        // }


        // $classe = apiFindOrFail($classe, $classe_id, "no such class");
        $classe = $ClasseService->loadWithWorkedHoursAndStudentsMissingHoursSum(
            classe_id: $classe_id,
            currentYear_id: $currentYear->id,
            module_id: null,
            timestamp1: $timestamp1,
            timestamp2: $timestamp2
        );


        // $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        // $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;


        // if ($timestamp1 === null && $timestamp2 === null) {

        //     $nbre_heure_effectue = $ClasseService->getClasseModulesWorkedHours($classe->modules);
        // } else if ($timestamp1 !== null && $timestamp2 !== null) {

        //     $classeSeances = $classe->seances()->where('etat', seanceStateEnum::Done->value)->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();
        //     $nbre_heure_effectue = $ClasseService->getClasseSeancesWorkedHours($classeSeances);
        // } else if ($timestamp1 !== null && $timestamp2 === null) {

        //     $classeSeances = $classe->seances()->where('etat', seanceStateEnum::Done->value)->where('heure_debut', '>', $timestamp1)->get();
        //     $nbre_heure_effectue = $ClasseService->getClasseSeancesWorkedHours($classeSeances);
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

        ['strudentAttendanceRate' => $strudentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate] = $ClasseService->getClasseAttendanceRates($classe, $timestamp1, $timestamp2);

        return apiSuccess(data: ['strudentAttendanceRate' => $strudentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate]);
    }

    public function getClasssesAttendanceRate()
    {

        $ClasseService = new ClasseService;
        $currentYear = app(AnneeService::class)->getCurrentYear();

        $classes = $ClasseService->loadWithWorkedHoursAndStudentsMissingHoursSum(new Classe, $currentYear->id);

        $response =  ClasseAttendanceRateResource::collection($classes->get());
        return apiSuccess(data: $response);
    }

    public function getModuleAttendanceRate($student_id, $module_id, $timestamp1 = null, $timestamp2 = null)
    {

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;
        $currentYear_id = null;
        if ($timestamp1 === null && $timestamp2 === null) {
            $currentYear_id = app(AnneeService::class)->getCurrentYear()->id;
        }

        $StudentService = new StudentService;



        // if ($timestamp1 === null && $timestamp2 === null) {

        //     // $student = User::with(['etudiantsClasses.modules', 'etudiantAbsences.seance']);

        //     // $student = User::with(['etudiantsClasses'=>function($query) use($student_id,$ClasseService,$module_id,$currentYear, $timestamp2, $timestamp1,$currentYear_id){

        //     //     // $query->withCount([
        //     //     //     'etudiants' => function ($query) use ($currentYear, $module_id, $student_id) {
        //     //     //         $query->where('users.id',$student_id);
        //     //     //         // $query->where('classe_etudiants.annee_id', $currentYear->id);
        //     //     //     }
        //     //     // ]);
        //     //     // ->with(['etudiants' => function ($query) use ($currentYear_id, $module_id, $timestamp2, $timestamp1) {
        //     //     //     $query->wherePivot('classe_etudiants.annee_id', $currentYear_id);
        //     //     //     $query->withSum(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear_id, $module_id, $timestamp2, $timestamp1) {

        //     //     //         if ($module_id !== null) {
        //     //     //             $query->where('module_id', $module_id);
        //     //     //         }
        //     //     //         if ($timestamp1 === null && $timestamp2 === null) {
        //     //     //             $query->where('annee_id', $currentYear_id);
        //     //     //         }
        //     //     //         if ($timestamp1 !== null && $timestamp2 === null) {
        //     //     //             $query->where('seance_heure_debut', '>', $timestamp1);
        //     //     //         }

        //     //     //         if ($timestamp1 !== null && $timestamp2 !== null) {
        //     //     //             $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
        //     //     //         };
        //     //     //     }], 'duree');
        //     //     // }])
        //     //     // ->withSum(['modules as workedHoursSum' => function ($query) use ($currentYear_id, $module_id) {
        //     //     //     $query->where('annee_id', $currentYear_id);
        //     //     //     if ($module_id !== null) {
        //     //     //         $query->where('module_id', $module_id);
        //     //     //     }
        //     //     // }], 'classe_module.nbre_heure_effectue')
        //     //     // ;


        //     //     $ClasseService->loadWithWorkedHoursAndStudentsMissingHoursSum(
        //     //                                                         classe_id:$query,
        //     //                                                         module_id:$module_id,
        //     //                                                         currentYear_id:$currentYear->id,
        //     //                                                         timestamp2:null,
        //     //                                                         timestamp1:null,

        //     //                                                     );
        //     // }]);


        //     // $student = $StudentService->getMissedHours($student, $currentYear->id, $module_id);
        // } else {
        //     $student = User::with(['etudiantsClasses.seances.absences']);
        // }


        $student = User::eagerLoadStudentWorkedAndMissedHours($module_id, $currentYear_id, $timestamp1, $timestamp2);

        $student = apiFindOrFail($student, $student_id, "no such student");




        // $studentClasse = $student->etudiantsClasses()->wherePivot('annee_id', $currentYear->id)->first();

        // $missingHoursCount = 0;
        $missingHoursCount =  (int) $student->missedHoursSum;

        $workedHours = 0;



        // if ($timestamp1 === null && $timestamp2 === null) {

        // $studentAbsences = $student->etudiantAbsences()->whereHas('seance', function ($query) use ($currentYear, $module_id) {
        //     $query->where([
        //         'annee_id' => $currentYear->id,
        //         'module_id' => $module_id,
        //     ]);
        // })->get();

        // $studentAbsences= $StudentService->getModuleAbsences($student,$module_id,$currentYear);


        // $missingHoursCount = $ClasseService->getStudentMissedHours($studentAbsences);



        // return [$nbre_heure_effectue,$student];
        // $nbre_heure_effectue = (int) $student->workedHoursSum;


        //  $nbre_heure_effectue =  $ClasseService->getClasseModulesWorkedHours($classesModules);


        // $classesModules = $studentClasse->modules()->wherePivot('annee_id', $currentYear->id)->where('modules.id', $module_id)->get();

        // $nbre_heure_effectue =  $ClasseService->getClasseModulesWorkedHours($classesModules);
        // }

        // if($timestamp1 !== null||$timestamp2 !== null){
        //     foreach ($student->etudiantsClasses as $classe){

        //         $workedHours += (int) $classe->workedHoursSum;
        //     }
        // }

        // if ($timestamp1 !== null && $timestamp2 !== null) {



        //     $seancesClasses = $studentClasse->seances()->where(['etat' => seanceStateEnum::Done->value, 'module_id' => $module_id])->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();

        //     ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        // }


        // if ($timestamp1 !== null && $timestamp2 === null) {

        //     $seancesClasses = $studentClasse->seances()->where(['etat' => seanceStateEnum::Done->value, 'module_id' => $module_id])->where('heure_debut', '>', $timestamp1)->get();



        //     ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        // }

        foreach ($student->etudiantsClasses as $classe) {
            $workedHours += (int) $classe->workedHoursSum;
        }


        $attendancePercentage = $StudentService->AttendancePercentageCalc($missingHoursCount, $workedHours);

        $maxAttendanceMark = 15;

        $attendanceMark = $StudentService->calcAttendanceMark($attendancePercentage, $maxAttendanceMark);

        $response = ["hours worked" => $workedHours, 'missing hours' => $missingHoursCount, 'presence percentage' => $attendancePercentage, 'attendance mark' => $attendanceMark];
        return apiSuccess(data: $response);
    }

    public function getClasseModuleAttendanceRate($classe_id, $module_id, $timestamp1 = null, $timestamp2 = null)
    {
        $ClasseService = new ClasseService;
        $currentYear = app(AnneeService::class)->getCurrentYear();

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;

        // $baseQuery =  Classe::withCount(['etudiants' => function ($query) use ($currentYear, $module_id) {
        //     $query->where('classe_etudiants.annee_id', $currentYear->id);
        // }
        // ])->with(['etudiants' => function ($query) use ($currentYear, $module_id, $timestamp2, $timestamp1) {
        // $query->wherePivot('classe_etudiants.annee_id', $currentYear->id);
        // $query->withSum(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear, $module_id, $timestamp2, $timestamp1) {

        //     if ($module_id !== null) {
        //         $query->where('module_id', $module_id);
        //     }
        //     if($timestamp1 === null && $timestamp2 === null){
        //         $query->where('annee_id', $currentYear->id);   
        //     }
        //     if ($timestamp1 !== null && $timestamp2 === null) {
        //         $query->where('seance_heure_debut', '>', $timestamp1);
        //     }

        //     if ($timestamp1 !== null && $timestamp2 !== null) {
        //         $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
        //     };
        // }], 'duree');
        // }]);

        // if ($timestamp1 === null && $timestamp2 === null) {


        //     // $classe = Classe::withCount(['etudiants' => function ($query) use ($currentYear, $module_id) {
        //     //         $query->where('classe_etudiants.annee_id', $currentYear->id);
        //     //     }
        //     // ])
        //     // ->with(['etudiants' => function ($query) use ($currentYear, $module_id) {
        //     //     $query->wherePivot('classe_etudiants.annee_id', $currentYear->id);
        //     //     $query->withSum(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear, $module_id) {
        //     //         $query->where('annee_id', $currentYear->id);
        //     //         if ($module_id !== null) {
        //     //             $query->where('module_id', $module_id);
        //     //         }
        //     //     }], 'duree');
        //     // }])

        //     $classe= $baseQuery->withSum(['modules as workedHoursSum' => function ($query) use ($currentYear, $module_id) {
        //             $query->where('annee_id', $currentYear->id);
        //             if ($module_id !== null) {
        //                 $query->where('module_id', $module_id);
        //             }
        //         }], 'classe_module.nbre_heure_effectue');
        // } else {



        //         // with(['etudiants.etudiantAbsences'=>function($query) use($currentYear,$module_id,$timestamp2,$timestamp1)  {


        //         //     if($module_id!==null){
        //         //         $query->where('module_id',$module_id);
        //         //     }

        //         //     if ($timestamp1 !== null && $timestamp2 === null) {
        //         //         $query->where('seance_heure_debut', '>', $timestamp1);

        //         //         }

        //         //     if ($timestamp1 !== null && $timestamp2 !== null) {
        //         //              $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
        //         //     };


        //         // }])


        //         $classe=$baseQuery->withSum(['seances as workedHoursSum' => function ($query) use ($module_id, $currentYear, $timestamp2, $timestamp1) {

        //             $baseWhereClause = ['etat' => seanceStateEnum::Done->value];



        //             if ($module_id !== null) {
        //                 $baseWhereClause['module_id'] = $module_id;
        //             }


        //             $query->where($baseWhereClause);

        //             if ($timestamp1 !== null && $timestamp2 === null) {
        //                 $query->where('heure_debut', '>', $timestamp1);
        //             }

        //             if ($timestamp1 !== null && $timestamp2 !== null) {
        //                 $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
        //             };
        //         }], 'duree')
        //         // ->with('seances',function($query)use($module_id,$currentYear,$timestamp2,$timestamp1){

        //         //     $baseWhereClause=['etat'=> seanceStateEnum::Done->value];



        //         //     if($module_id!==null){
        //         //         $baseWhereClause['module_id']=$module_id;
        //         //     }


        //         //     $query->where($baseWhereClause);

        //         //     if ($timestamp1 !== null && $timestamp2 === null) {
        //         //         $query->where('heure_debut', '>', $timestamp1);

        //         //         }

        //         //         if ($timestamp1 !== null && $timestamp2 !== null) {
        //         //              $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
        //         //         };

        //         // })

        //     ;
        // }



        // $classe = apiFindOrFail($classe, $classe_id, "no such class");


        $classe = $ClasseService->loadWithWorkedHoursAndStudentsMissingHoursSum($classe_id, $currentYear->id, $module_id, $timestamp1, $timestamp2);


        $response = $ClasseService->getClasseAttendanceRates($classe, $timestamp1, $timestamp2, $module_id, $currentYear->id);

        return apiSuccess(data: $response);
    }

    public function getStudentAbsences($student_id, $timestamp1 = null, $timestamp2 = null)
    {

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;


        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        $student =  User::with(
            ['etudiantAbsences' => function ($query) use ($timestamp1, $timestamp2, $currentYearId) {
                if ($timestamp1 === null && $timestamp2 === null) {
                    $query->where("annee_id", $currentYearId);
                }



                if ($timestamp1 !== null) {

                    if ($timestamp2 === null) {

                        $query->where('seance_heure_debut', '>', $timestamp1);
                    }

                    if ($timestamp2 !== null) {

                        $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
                    }
                }
                $query->with('seance.typeSeance');
            }]
        );

        $student = apiFindOrFail($student, $student_id);
        $studentAbsences = $student->etudiantAbsences;

        // if ($timestamp1 === null && $timestamp2 === null) {
        //     $studentAbsences = $baseQuery->where("annee_id", $currentYear->id)->get();
        // }

        // if ($timestamp1 !== null) {

        //     $studentAbsences = $baseQuery->whereHas('seance', function ($query) use ($timestamp1, $timestamp2) {

        //         if ($timestamp2 === null) {


        //             $query->where('heure_debut', '>', $timestamp1);
        //         }

        //         if ($timestamp2 !== null) {

        //             $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
        //         }
        //     })->get();
        // }

        $response = new AbsenceCollection($studentAbsences);
        return apiSuccess(data: $response);
    }

    public function getStudentAttendanceRateByYearSegment(Request  $request, $student_id, $year_segments = null)

    {
        $regexPattern = '/,?\d+,?/i';
        $validator = Validator::make($request->route()->parameters(), [
            'year_segments' => [
                'nullable',
                "regex:$regexPattern",


            ]

        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }


        $validated = $validator->validated();

        if ($year_segments !== null) {
            $selectedYearSegments =  str_replace(' ', '', $validated['year_segments']);

            preg_match_all($regexPattern, $selectedYearSegments, $matches);
            $selectedYearSegments = $matches[0];
            function getYearSegmentIntVal($yearSegment)
            {
                $yearSegment =  str_replace(',', '', $yearSegment);
                return intval($yearSegment);
            }

            $selectedYearSegments = array_map('App\Http\Controllers\getYearSegmentIntVal', $selectedYearSegments);
        }



        $StudentService = new StudentService;

        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        $student =  User::with([
            'etudiantsClasses' => function ($query) use ($currentYearId) {
                $query->where('annee_id', $currentYearId);

                $query->with('seances', function ($query) use ($currentYearId) {
                    $query->with('absences');
                    $query->where([
                        'annee_id' => $currentYearId,
                        'etat' => seanceStateEnum::Done->value
                    ]);
                });
            }
        ]);
        // .seances.absences'
        $student = apiFindOrFail($student, $student_id);


        $studentClasse = $student->etudiantsClasses->first();


        // $seances = $studentClasse->seances()->where([
        //     'annee_id' => $currentYearId,
        //     'etat' => seanceStateEnum::Done->value
        // ])->get();

        $yearSegmentBaseQuery = YearSegment::where('annee_id', $currentYearId);

        if ($year_segments !== null) {
            $yearSegmentBaseQuery = $yearSegmentBaseQuery->whereIn('number', $selectedYearSegments);
        }
        $yearSegments = $yearSegmentBaseQuery->get();


        function isSeanceInYearSegments($seance, $yearSegments)
        {
            $seanceStart = Carbon::parse($seance->heure_debut);
            $filtered =  $yearSegments->filter(function ($yearSegment, $key) use ($seanceStart) {

                $yearSegmentStart = Carbon::parse($yearSegment->start);
                $yearSegmentEnd = Carbon::parse($yearSegment->end);

                //  apiSuccess(data: ['key' => $key ,'yearS' => $yearSegmentStart, 'yearSegmentEnd'=>$yearSegmentEnd, 'seanceStart' => $seanceStart])->send();
                //     die();
                return  $seanceStart->greaterThanOrEqualTo($yearSegmentStart)  && $seanceStart->lessThanOrEqualTo($yearSegmentEnd);
            });

            return $filtered;
        }

        foreach ($studentClasse->seances as $seance) {

            $seancesYearSegments = isSeanceInYearSegments($seance, $yearSegments);

            if ($seancesYearSegments->isEmpty()) continue;



            $seanceStart = Carbon::parse($seance->heure_debut);
            $seanceEnd = Carbon::parse($seance->heure_fin);
            $seanceDuration = $seance->duree;



            $currentSeanceYearSegment = $seancesYearSegments->first();



            if (isset($currentSeanceYearSegment->workedHours)) {
                $currentSeanceYearSegment->workedHours += $seanceDuration;
            } else {
                $currentSeanceYearSegment->workedHours = $seanceDuration;
            }


            if ($seance->absences->contains(function ($absence, int $key) use ($student_id) {
                return $absence->user_id == $student_id;
            })) {

                if (isset($currentSeanceYearSegment->missedHours)) {
                    $currentSeanceYearSegment->missedHours += $seanceDuration;
                } else {
                    $currentSeanceYearSegment->missedHours = $seanceDuration;
                }
            } else {
                if (!isset($currentSeanceYearSegment->missedHours)) {
                    $currentSeanceYearSegment->missedHours = 0;
                }
            }

            $currentSeanceYearSegment->attendanceRate = $StudentService->AttendancePercentageCalc($currentSeanceYearSegment->missedHours, $currentSeanceYearSegment->workedHours);
        }


        $response = YearSegmentAbsenceResource::collection($yearSegments);

        return apiSuccess(data: $response);
    }

    public function justifyStudentAbsence(Request $request, $absence_id)
    {

        $validator = Validator::make($request->all(), [
            'receipt' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'comments' => 'string',
            'coordinateur_id' => 'integer'

        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $ClasseService = new ClasseService;
        $StudentService = new StudentService;
        $currentYear = app(AnneeService::class)->getCurrentYear();


        $fileName = null;

        if ($request->has('receipt')) {
            $receipt = $request->file('receipt');
            $fileName = $receipt->store('receipts');
        }


        $absence =  Absence::with(['etudiant:id', 'seance.classe']);


        $absence = apiFindOrFail($absence, $absence_id, "no absence found");


        $student = $absence->etudiant;

  

        $module_id = $absence->seance->module_id;

        $studentClasse = $absence->seance->classe;

        $seance_id = $absence->seance->id;

        $coordinateur_id = $studentClasse->coordinateur_id;

        if ($request->has('coordinateur_id')) {
            $coordinateur_id = $request->coordinateur_id;
        }

        $StudentService->loadStudentmissedHoursSum($student, $module_id, $currentYear->id, loading: 'load', callback: function ($seanceQuery) use ($seance_id) {
            $seanceQuery->where('seance_id', '!=', $seance_id)
                ->Where('etat', absenceStateEnum::notJustified->value);
        });

        // return $student;

        // $studentClasse = $absence->etudiant()->etudiantsClasses()->where()->first();

        // $pivotDataBaseQuery = DB::table('classe_module')
        //     ->where([
        //         'annee_id' => $currentYear->id,
        //         'module_id' => $module_id,
        //         'classe_id' => $studentClasse->id
        //     ]);


        $pivotDataBaseQuery =  $ClasseService->getClasseModuleQuery($currentYear->id, $module_id, $studentClasse->id);

        $pivotData = $pivotDataBaseQuery->first();
   

    
        $totalModuleHours = $pivotData->nbre_heure_total;

        $workedHours = $pivotData->nbre_heure_effectue;

        $workedHoursPercentage = round((100 * $workedHours) / $totalModuleHours, 2);

        $minWorkedHoursPercentage = 30;

        $minAttendancePercentage = 30;

        if ($workedHoursPercentage > $minWorkedHoursPercentage) {

            // $studentModuleAbscences= $StudentService->getModuleAbsences($student,$module_id,$currentYear,function($seanceQuery) use($seance_id){
            //     $seanceQuery->where('seance_id','!=',$seance_id)->Where('etat', absenceStateEnum::notJustified->value);
            // });
            // $missedHours =  $ClasseService->getStudentMissedHours($studentModuleAbscences);

            // $missedHours =  $StudentService->getMissedHours(
            //     student_id: $student->id,
            //     currentYear_id: $currentYear->id,
            //     module_id: $module_id,
                // callback: function ($seanceQuery) use ($seance_id) {
                //     $seanceQuery->where('seance_id', '!=', $seance_id)
                //         ->Where('etat', absenceStateEnum::notJustified->value);
                // }
            // );

            $missedHours = $student->missedHoursSum;

            // return $missedHours;

            $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

            if ($unJustifiedpresencePercentage > $minAttendancePercentage && $pivotData->statut_cours == false) {
                Droppe::where([
                    'user_id' => $student->id,
                    'module_id' => $module_id,
                    'annee_id' => $currentYear->id
                ])->update(['isDropped' => false, 'updated_at' => $absence->seance_heure_debut ]);
            }
        }


        $absence->update(['etat' => absenceStateEnum::justified->value, 'receipt' => $fileName, 'coordinateur_id' => $coordinateur_id]);

        return apiSuccess(message: 'absence justified successfully !');
    }
}
