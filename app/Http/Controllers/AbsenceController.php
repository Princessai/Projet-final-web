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
use App\Http\Resources\ClasseStudentsAbsencesResource;

 include_once (base_path('utilities\seeder\seanceDuration.php'));


class AbsenceController extends Controller
{
    public function getStudentAttendanceRate($student_id, $timestamp1 = null, $timestamp2 = null)
    {

       
        $currentYear = (new AnneeService)->getCurrentYear();
        $StudentService = new StudentService;
        $ClasseService = new ClasseService;
        if ($timestamp1 === null && $timestamp2 === null) {

            $student = User::with(['etudiantsClasses.modules', 'etudiantAbsences.seance']);
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

            $studentAbsences = $student->etudiantAbsences()->where(['annee_id' => $currentYear->id])->get();


            // foreach ($studentAbsences as $studentAbsence) {
            //     $seance = $studentAbsence->seance;
            //     $startHour =  Carbon::parse($seance->heure_debut);
            //     $endHour =  Carbon::parse($seance->heure_fin);

            //     $duree = seanceDuration($endHour, $startHour);


            //     $missingHoursCount += $duree;
            // }


            $missingHoursCount = $ClasseService->getStudentMissedHours($studentAbsences);

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

        if ($timestamp1 === null && $timestamp2 === null) {
            $classe = Classe::with(['etudiants.etudiantAbsences', 'modules']);
        } else {
            $classe = Classe::with(['etudiants.etudiantAbsences', 'seances']);
        }

        $classe = apiFindOrFail($classe, $classe_id, "no such class");

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
        $classes = Classe::all();
        $response =  ClasseAttendanceRateResource::collection($classes);
        return apiSuccess(data: $response);
    }

    public function getModuleAttendanceRate($student_id, $module_id, $timestamp1 = null, $timestamp2 = null)
    {

        $currentYear = (new AnneeService)->getCurrentYear();
        $StudentService = new StudentService;
        $ClasseService = new ClasseService;

        if ($timestamp1 === null && $timestamp2 === null) {

            $student = User::with(['etudiantsClasses.modules', 'etudiantAbsences.seance']);
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

            // $studentAbsences = $student->etudiantAbsences()->whereHas('seance', function ($query) use ($currentYear, $module_id) {
            //     $query->where([
            //         'annee_id' => $currentYear->id,
            //         'module_id' => $module_id,
            //     ]);
            // })->get();

            $studentAbsences= $StudentService->getModuleAbsences($student,$module_id,$currentYear);


            $missingHoursCount = $ClasseService->getStudentMissedHours($studentAbsences);

            $classesModules = $studentClasse->modules()->wherePivot('annee_id', $currentYear->id)->where('modules.id', $module_id)->get();

            $nbre_heure_effectue =  $ClasseService->getClasseModulesWorkedHours($classesModules);
        }


        if ($timestamp1 !== null && $timestamp2 !== null) {



            $seancesClasses = $studentClasse->seances()->where(['etat' => seanceStateEnum::Done->value, 'module_id' => $module_id])->whereBetween('heure_debut', [$timestamp1, $timestamp2])->get();

            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        }


        if ($timestamp1 !== null && $timestamp2 === null) {

            $seancesClasses = $studentClasse->seances()->where(['etat' => seanceStateEnum::Done->value, 'module_id' => $module_id])->where('heure_debut', '>', $timestamp1)->get();



            ['workedHours' => $nbre_heure_effectue, 'missedHours' => $missingHoursCount] = $ClasseService->getStudentMissedAndWorkedHours($seancesClasses, $student_id);
        }


 

        $presencePercentage = $StudentService->AttendancePercentageCalc($missingHoursCount, $nbre_heure_effectue);

        $maxAttendanceMark = 15;

        $attendanceMark = round(($presencePercentage * 20) / 100, 2);
        $attendanceMark = min($maxAttendanceMark, $attendanceMark);
        $response = ["hours worked" => $nbre_heure_effectue, 'missing hours' => $missingHoursCount, 'presence percentage' => $presencePercentage, 'attendance mark' => $attendanceMark];
        return apiSuccess(data: $response);
    }

    public function getClasseModuleAttendanceRate($classe_id, $module_id, $timestamp1 = null, $timestamp2 = null)
    {
        $ClasseService = new ClasseService;

        if ($timestamp1 === null && $timestamp2 === null) {
            $classe = Classe::with(['etudiants.etudiantAbsences', 'modules']);
        } else {
            $classe = Classe::with(['etudiants.etudiantAbsences', 'seances']);
        }

        $classe = apiFindOrFail($classe, $classe_id, "no such class");



        $response = $ClasseService->getClasseAttendanceRates($classe, $timestamp1, $timestamp2, $module_id);

        return apiSuccess(data: $response);
    }

    public function getStudentAbsences($student_id, $timestamp1 = null, $timestamp2 = null)
    {

        $currentYear = (new AnneeService)->getCurrentYear();
        $student =  User::with('etudiantAbsences');
        $student = apiFindOrFail($student, $student_id);
        $baseQuery = $student->etudiantAbsences();
        if ($timestamp1 === null && $timestamp2 === null) {
            $studentAbsences = $baseQuery->where("annee_id", $currentYear->id)->get();
        }

        if ($timestamp1 !== null) {

            $studentAbsences = $baseQuery->whereHas('seance', function ($query) use ($timestamp1, $timestamp2) {

                if ($timestamp2 === null) {
                    $query->where('heure_debut', '>', $timestamp1);
                }

                if ($timestamp2 !== null) {
                    $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                }
            })->get();
        }

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
    
        $currentYear = (new AnneeService)->getCurrentYear();
        $student =  User::with(['etudiantsClasses.seances.absences']);
        $student = apiFindOrFail($student, $student_id);
        $studentClasse = $student->etudiantsClasses()->wherePivot('annee_id', $currentYear->id)->first();
        $seances = $studentClasse->seances()->where([
            'annee_id' => $currentYear->id,
            'etat' => seanceStateEnum::Done->value
        ])->get();

        $yearSegmentBaseQuery = YearSegment::where('annee_id', $currentYear->id);

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

        foreach ($seances as $seance) {

            $seancesYearSegments = isSeanceInYearSegments($seance, $yearSegments);

            if ($seancesYearSegments->isEmpty()) continue;



            $seanceStart = Carbon::parse($seance->heure_debut);
            $seanceEnd = Carbon::parse($seance->heure_fin);
            $seanceDuration = seanceDuration($seanceEnd, $seanceStart);



            $currentSeanceYearSegment = $seancesYearSegments->first();



            if (isset($currentSeanceYearSegment->workedHours)) {
                $currentSeanceYearSegment->workedHours += $seanceDuration;
            } else {
                $currentSeanceYearSegment->workedHours = $seanceDuration;
            }


            if ($seance->absences()->where('user_id', $student_id)->exists()) {

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
        $ClasseService =new ClasseService;
        $StudentService = new StudentService;
        $AnneeService = new AnneeService;

        $validator = Validator::make($request->all(), [
            'receipt' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'comments' => 'string',
            'coordinateur_id'=>'integer'

        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $fileName = null;

        if ($request->has('receipt')) {
            $receipt = $request->file('receipt');
            $fileName = $receipt->store('receipts');
        }
    

        $currentYear = $AnneeService->getCurrentYear();
        $absence =  Absence::with(['etudiant.etudiantAbsences.seance', 'seance.classe']);

        $absence = apiFindOrFail($absence, $absence_id, "no absence found");


        $student = $absence->etudiant;
        $module_id = $absence->seance->module_id;
        $studentClasse = $absence->seance->classe;
        $seance_id =$absence->seance->id;
        $coordinateur_id = $studentClasse->coordinateur_id;
        if ($request->has('coordinateur_id')) {
            $coordinateur_id=$request->coordinateur_id;
         }
        

        // $studentClasse = $absence->etudiant()->etudiantsClasses()->where()->first();

        $pivotDataBaseQuery = DB::table('classe_module')
            ->where([
                'annee_id' => $currentYear->id,
                'module_id' => $module_id,
                'classe_id' => $studentClasse->id
            ]);

        $pivotData = $pivotDataBaseQuery->first();
        $totalModuleHours = $pivotData->nbre_heure_total;
        $workedHours = $pivotData->nbre_heure_effectue;

        $workedHoursPercentage = round((100 * $workedHours) / $totalModuleHours, 2);
        $minWorkedHoursPercentage = 30;

        $minAttendancePercentage = 30;
      
        if($workedHoursPercentage>$minWorkedHoursPercentage){
            
            $studentModuleAbscences= $StudentService->getModuleAbsences($student,$module_id,$currentYear,function($seanceQuery) use($seance_id){
                $seanceQuery->where('seance_id','!=',$seance_id)->Where('etat', absenceStateEnum::notJustified->value);
            });
    
    
                $missedHours =  $ClasseService->getStudentMissedHours($studentModuleAbscences);
                $unJustifiedpresencePercentage = $StudentService->AttendancePercentageCalc($missedHours, $workedHours);
    
                if ($unJustifiedpresencePercentage > $minAttendancePercentage) {
                    Droppe::where([
                        'user_id' => $student->id,
                        'module_id' => $module_id,
                        'annee_id' => $currentYear->id
                    ])->delete();
                       
                }
        }
      
   
        $absence->update(['etat' => absenceStateEnum::justified->value,'receipt' => $fileName,'coordinateur_id' => $coordinateur_id]);

        return apiSuccess(message: 'absence justified successfully !');
    }
}
