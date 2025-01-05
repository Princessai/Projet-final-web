<?php

namespace App\Http\Controllers;

use App\Http\Resources\TimetableResource;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


include_once(base_path('utilities\seeder\seanceDuration.php'));


class AbsenceController extends Controller
{
    public function getStudentAttendanceRate($student_id, $timestamp1 = null, $timestamp2 = null)
    {


        $currentYear = (new AnneeService)->getCurrentYear();
        $StudentService = new StudentService;
        $ClasseService = new ClasseService;
        if ($timestamp1 === null && $timestamp2 === null) {
            $student = User::with(['etudiantsClasses' => function ($query) use ($currentYear) {
                $query->wherePivot('annee_id', $currentYear->id);
                $query->with(['modules' => function ($query) use ($currentYear) {
                    $query->wherePivot('annee_id', $currentYear->id);
                }]);
            }]);
            $student = $StudentService->getMissedHours($student, $currentYear->id);
        } else {
            $student = User::with(['etudiantsClasses' => function ($query) use ($currentYear, $timestamp1, $timestamp2) {
                $query->wherePivot('annee_id', $currentYear->id);
                $query->with(['seances' => function ($query) use ($timestamp1, $timestamp2) {
                    $query->where('etat', seanceStateEnum::Done->value);


                    if ($timestamp1 === null && $timestamp2 === null) {
                        $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                    }
                    if ($timestamp1 !== null && $timestamp2 === null) {
                        $query->where('heure_debut', '>', $timestamp1);
                    }
                    $query->with('absences');
                }]);
            }]);
        }



        $student = apiFindOrFail($student, $student_id, "no such student");






        $studentClasse = $student->etudiantsClasses->first();


        $missingHoursCount = 0;

        $nbre_heure_effectue = 0;

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;

        if ($timestamp1 === null && $timestamp2 === null) {



            $missingHoursCount = (int) $student->missingHoursSum;


            $classesModules = $studentClasse->modules;


            $nbre_heure_effectue =  $ClasseService->getClasseModulesWorkedHours($classesModules);
        }


        if ($timestamp1 !== null && $timestamp2 !== null) {


            $seancesClasses = $studentClasse->seances;


            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        }


        if ($timestamp1 !== null && $timestamp2 === null) {

            $seancesClasses = $studentClasse->seances;



            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        }





        $presencePercentage = $StudentService->AttendancePercentageCalc($missingHoursCount, $nbre_heure_effectue);

        $maxAttendanceMark = 15;

        $attendanceMark = round(($presencePercentage * 20) / 100, 2);
        $attendanceMark = min($maxAttendanceMark, $attendanceMark);
        $response = ["workedHours" => $nbre_heure_effectue, 'missingHours' => $missingHoursCount, 'attendanceRate' => $presencePercentage, 'attendanceMark' => $attendanceMark];
        return apiSuccess(data: $response);
    }

    public function getClassseStudentsAttendanceRate($classe_id, $timestamp1 = null, $timestamp2 = null)
    {
        $ClasseService = new ClasseService;
        $currentYear = app(AnneeService::class)->getCurrentYear();


        $classe = $ClasseService->loadWithWorkedHoursAndStudentsMissingHoursSum(
            classe_id: $classe_id,
            currentYear_id: $currentYear->id,
            module_id: null,
            timestamp1: $timestamp1,
            timestamp2: $timestamp2
        );




        $data = ['studentAttendanceRate' => $studentAttendanceRate, 'classeAttendanceRate' => $classeAttendanceRate] = $ClasseService->getClasseAttendanceRates($classe, $timestamp1, $timestamp2);

        return apiSuccess(data: $data);
    }

    public function getClasssesAttendanceRate()
    {

        $ClasseService = new ClasseService;
        $currentYear = app(AnneeService::class)->getCurrentYear();

        $classes = $ClasseService->loadWithWorkedHoursAndStudentsMissingHoursSum(new Classe, $currentYear->id);

        $response =  ClasseAttendanceRateResource::collection($classes->get());
        return apiSuccess(data: $response);
    }

    // public function getAttendanceRateByWeeks($student_id, $timestamp1 = null, $timestamp2 = null)
    // {

    //     $currentYear = app(AnneeService::class)->getCurrentYear();

    //     $query =  User::with(['etudiantsClasses' => function ($query) use ($student_id, $currentYear) {
    //         $query->with('timetables', function ($query) use ($student_id, $currentYear) {
    //             $query->withSum(['seances as workedHoursSum' => function ($query) {
    //                 $query->where('etat', seanceStateEnum::Done->value);
    //             }], 'duree');

    //             $query->withSum(['seances as missedHoursSum' => function ($query) use ($student_id, $currentYear) {
    //                 $query->where('etat', seanceStateEnum::Done->value);
    //                 $query->whereHas('absences', function ($query) use ($student_id, $currentYear) {
    //                     $query->where(['user_id' => $student_id, 'annee_id' => $currentYear->id]);
    //                 });
    //             }], 'duree');
    //         });
    //     }]);
    //     // ->with('etudiantAbsences', function ($query) use($currentYear) {
    //     //     $query->where('annee_id', $currentYear->id);
    //     // });

    //     $student = apiFindOrFail($query, $student_id, "no such user");

    //     $student  = $student->etudiantsClasses->reduce(function ($carry,  $item) {
    //         return $carry->merge($item->timetables);
    //     }, collect([]));

    //     $student =  $student->map(function ($student) {
    //         return new TimetableResource($student);
    //     });

    //     // $data = apiSuccess();


    //     return apiSuccess($student);
    // }


    public function getModuleAttendanceRate($student_id, $module_id, $timestamp1 = null, $timestamp2 = null)
    {

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;
        $currentYear_id = null;
        if ($timestamp1 === null && $timestamp2 === null) {
            $currentYear_id = app(AnneeService::class)->getCurrentYear()->id;
        }

        $StudentService = new StudentService;


        $student = User::eagerLoadStudentWorkedAndMissedHours($module_id, $currentYear_id, $timestamp1, $timestamp2);

        $student = apiFindOrFail($student, $student_id, "no such student");


        $missingHoursCount =  (int) $student->missedHoursSum;

        $workedHours = 0;


        foreach ($student->etudiantsClasses as $classe) {
            $workedHours += (int) $classe->workedHoursSum;
        }


        $attendancePercentage = $StudentService->AttendancePercentageCalc($missingHoursCount, $workedHours);

        $maxAttendanceMark = 15;

        $attendanceMark = $StudentService->calcAttendanceMark($attendancePercentage, $maxAttendanceMark);

        $response = ["workedHours" => $workedHours, 'missingHours' => $missingHoursCount, 'attendanceRate' => $attendancePercentage, 'attendanceMark' => $attendanceMark];
        return apiSuccess(data: $response);
    }

    public function getClasseModuleAttendanceRate($classe_id, $module_id, $timestamp1 = null, $timestamp2 = null)
    {
        $ClasseService = new ClasseService;
        $currentYear = app(AnneeService::class)->getCurrentYear();

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;

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

                $query->with('module');

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
                    $query->where([
                        'annee_id' => $currentYearId,
                        'etat' => seanceStateEnum::Done->value
                    ]);
                    $query->with('absences');
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
        $filePath = null;

        if ($request->hasFile('receipt')) {


            $uuid = Str::uuid();
            $receipt = $request->file('receipt');
            $fileExtension = $receipt->getClientOriginalExtension();
            $fileName = $uuid . '.' . $fileExtension;
            $dirName = "receipts";
            $dirPath = storage_path("app/public/$dirName");
            $receipt->move($dirPath, $fileName);
            $filePath = asset("storage/receipts/$fileName");
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


        $pivotDataBaseQuery =  $ClasseService->getClasseModuleQuery($currentYear->id, $module_id, $studentClasse->id);

        $pivotData = $pivotDataBaseQuery->first();



        $totalModuleHours = $pivotData->nbre_heure_total;

        $workedHours = $pivotData->nbre_heure_effectue;

        $workedHoursPercentage = round((100 * $workedHours) / $totalModuleHours, 2);

        $minWorkedHoursPercentage = 30;

        $minAttendancePercentage = 30;

        if ($workedHoursPercentage > $minWorkedHoursPercentage) {

            $missedHours = $student->missedHoursSum;


            $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);

            if ($unJustifiedpresencePercentage > $minAttendancePercentage && $pivotData->statut_cours == false) {
                Droppe::where([
                    'user_id' => $student->id,
                    'module_id' => $module_id,
                    'annee_id' => $currentYear->id
                ])->update(['isDropped' => false, 'updated_at' => $absence->seance_heure_debut]);
            }
        }


        $absence->update(['etat' => absenceStateEnum::justified->value, 'receipt' => $fileName, 'coordinateur_id' => $coordinateur_id]);
        $data = [];
        if ($fileName) {
            $data['fileName'] = $fileName;
            $data['filePath'] = $filePath;
        }

        return apiSuccess(data: $data, message: 'absence justified successfully !');
    }

    public  function  getStudentAttendanceRateByweeks($student_id, $annee_id, $timestamp1 = null, $timestamp2 = null)
    {

        $StudentService = new StudentService;

        $studentQuery = User::with(['etudiantsClasses' => function ($query) use ($student_id, $annee_id) {

            $query->wherePivot('annee_id', $annee_id);

            $query->with(['timetables' => function ($query) use ($student_id, $annee_id) {


                $dbRawDuree = DB::raw('SUM(seances.duree) as workedHoursSum');
                $dbRawAbsences = DB::raw('SUM(absences.duree) as missedHoursSum');

                $query->select(
                    'timetables.id',
                    'timetables.date_debut',
                    'timetables.date_fin',
                    'timetables.classe_id',
                    $dbRawDuree,
                    $dbRawAbsences,
                )
                    ->join('seances', function ($join) use ($annee_id) {

                        $join->on('timetables.id', '=', 'seances.timetable_id');
                        $join->where(['etat' => seanceStateEnum::Done->value, 'seances.annee_id' => $annee_id]);
                    })->leftJoin('absences', function ($join) use ($student_id, $annee_id) {

                        $join->on('seances.id', '=', 'absences.seance_id');
                        $join->where(['absences.user_id' => $student_id, 'absences.annee_id' => $annee_id]);
                    })->groupBy('timetable_id');
            }]);
        }]);


        $student = apiFindOrFail($studentQuery, $student_id);

        $classes = $student->etudiantsClasses;

        $response = [];

        foreach ($classes as $classe) {

            $timetables = $classe->timetables;


            foreach ($timetables as $timetable) {
                $workedHoursSum = $timetable->workedHoursSum;

                $missedHoursSum = $timetable->missedHoursSum;

                $attendanceRate = $StudentService->AttendancePercentageCalc($missedHoursSum, $workedHoursSum);

                $response[] = [
                    "attendanceRate" => $attendanceRate,
                    "date_debut" => $timetable->date_debut,
                    "date_fin" => $timetable->date_fin,
                    "classe" => $classe->label
                ];
            }
        }


        return apiSuccess(data: $response);
    }

    public  function  getStudentAttendanceRateByModules($student_id, $annee_id, $timestamp1 = null, $timestamp2 = null)
    {
        $currentYear = app(AnneeService::class)->getCurrentYear();

        $StudentService = new StudentService;


        $studentQuery = User::with(['etudiantsClasses' => function ($query) use ($currentYear) {
            $query->with('modules', function ($query) use ($currentYear) {
                $query->wherePivot('annee_id', $currentYear->id);
                $query->wherePivot('nbre_heure_effectue', '>', 0);
            });
        }])->with('etudiantAbsences', function ($query) use ($currentYear) {
            $query->select('*', DB::raw('SUM(duree) as missedHoursSum'));
            $query->where(['annee_id' => $currentYear->id]);

            $query->groupBy('module_id');
        });

        $student = apiFindOrFail($studentQuery, $student_id);

        $studentAbsences = $student->etudiantAbsences->keyBy(function ($absence, int $key) {
            return $absence->module_id;
        });

        $etudiantsClasses = $student->etudiantsClasses;

        $response = [];


        foreach ($etudiantsClasses as $etudiantsClasse) {
            foreach ($etudiantsClasse->modules as $module) {
                $module_id = $module->id;
                $workedHoursSum = $module->pivot->nbre_heure_effectue;
                $missedHoursSum = isset($studentAbsences[$module_id]) ? $studentAbsences[$module_id]->missedHoursSum : 0;

                $attendanceRate = $StudentService->AttendancePercentageCalc($missedHoursSum, $workedHoursSum);

                $response[] = [
                    "module_id" => $module_id,
                    "label" => $module->label,
                    "attendanceRate" => $attendanceRate
                ];
            }
        }


        return apiSuccess(data: $response);
    }
}
