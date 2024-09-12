<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Annee;
use App\Models\Classe;
use App\Models\YearSegment;
use Illuminate\Http\Request;
use App\Enums\seanceStateEnum;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CourseHourResource;
use App\Models\TypeSeance;
use App\Services\AnneeService;
use App\Services\ClasseService;

require(base_path('utilities\seeder\seanceDuration.php'));

function getYearSegmentIntVal($yearSegment)
{
    $yearSegment =  str_replace(',', '', $yearSegment);
    return intval($yearSegment);
}

class CourseHourController extends Controller
{
    public function getClasseWorkedHours(Request $request, $classe_id, $year_segments=null)
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

        $selectedYearSegments =  str_replace(' ', '', $validated['year_segments']);
        preg_match_all($regexPattern, $selectedYearSegments, $matches);
        $selectedYearSegments = $matches[0];

        $selectedYearSegments = array_map('App\Http\Controllers\getYearSegmentIntVal', $selectedYearSegments);




        $classeService = new ClasseService;
        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        $classe = Classe::with('seances');
        $classe = apiFindOrFail($classe, $classe_id, 'no such classe');
        $baseQueryYearSegments=YearSegment::where('annee_id', $currentYearId);
        if($year_segments!==null){
            $baseQueryYearSegments= $baseQueryYearSegments->whereIn('number', $selectedYearSegments); 
        }
        $yearSegments = $baseQueryYearSegments->get();


        //     $seances=$classe->seances()->where([
        //         'annee_id'=> $currentYear->id,
        //         'etat'=>seanceStateEnum::Done->value
        // ])->get();

        //     function isSeanceInYearSegments($seance, $yearSegments)
        //     {
        //         $seanceStart = Carbon::parse($seance->heure_debut);
        //         $filtered =  $yearSegments->filter(function ($yearSegment, $key) use ($seanceStart) {

        //             $yearSegmentStart = Carbon::parse($yearSegment->start);
        //             $yearSegmentEnd = Carbon::parse($yearSegment->end);

        //             //  apiSuccess(data: ['key' => $key ,'yearS' => $yearSegmentStart, 'yearSegmentEnd'=>$yearSegmentEnd, 'seanceStart' => $seanceStart])->send();
        //             //     die();
        //             return  $seanceStart->greaterThanOrEqualTo($yearSegmentStart)  && $seanceStart->lessThanOrEqualTo($yearSegmentEnd);
        //         });

        //         return $filtered;
        //     }

        //     foreach ($seances as $seance) {

        //         $seancesYearSegments = isSeanceInYearSegments($seance, $yearSegments);

        //         if ($seancesYearSegments->isEmpty()) continue;



        //         $seanceStart = Carbon::parse($seance->heure_debut);
        //         $seanceEnd = Carbon::parse($seance->heure_fin);
        //         $seanceDuration = seanceDuration($seanceEnd, $seanceStart);



        //         $currentSeanceYearSegment = $seancesYearSegments->first();



        //         if (isset($currentSeanceYearSegment->workedHours)) {
        //             $currentSeanceYearSegment->workedHours += $seanceDuration;
        //         } else {
        //             $currentSeanceYearSegment->workedHours = $seanceDuration;
        //         }



        //     }
        $typeSeances = TypeSeance::all();
        $yearSegments = $classeService->getYearSegmentsWorkedHours($classe, $yearSegments, $currentYearId, $typeSeances);
        $response = CourseHourResource::collection($yearSegments);

        return apiSuccess(data: $response);
    }

    public function getAllClassesWorkedHours(Request $request,  $year_segments)
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

        $selectedYearSegments =  str_replace(' ', '', $validated['year_segments']);
        preg_match_all($regexPattern, $selectedYearSegments, $matches);
        $selectedYearSegments = $matches[0];
        // function getYearSegmentIntVal($yearSegment)
        // {
        //     $yearSegment =  str_replace(',', '', $yearSegment);
        //     return intval($yearSegment);
        // }
        $selectedYearSegments = array_map('App\Http\Controllers\getYearSegmentIntVal', $selectedYearSegments);


        $classes = Classe::with('seances')->get();


        $classeService = new ClasseService;
        $currentYear = Annee::latest()->first();


        $yearSegments = YearSegment::where('annee_id', $currentYear->id)->whereIn('number', $selectedYearSegments)->get();
        $typeSeances = TypeSeance::all();
        $response = [];
        foreach ($classes as $classe) {
            $yearSegmentsCopy = $classeService->getYearSegmentsWorkedHours($classe, $yearSegments, $currentYear->id, $typeSeances);  
            $response[] =   ['classe_id'=> $classe->id, 'yearSegments' => CourseHourResource::collection($yearSegmentsCopy)];
        }







        return apiSuccess(data: $response);
    }
}
