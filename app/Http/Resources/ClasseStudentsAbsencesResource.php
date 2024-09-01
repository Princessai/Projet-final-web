<?php

namespace App\Http\Resources;

use Carbon\Carbon;

use Illuminate\Http\Request;

use App\Services\StudentService;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseStudentsAbsencesResource extends JsonResource
{
    private $nbre_heure_effectue;
    private $module_id;

    public function __construct($resource, $nbre_heure_effectue, $module_id = null)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;

        $this->nbre_heure_effectue = $nbre_heure_effectue;

        $this->module_id = $module_id;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array

    {
   
        $studentService = new StudentService;
  

        $timestamp1 =  $request->route('timestamp1');
        $timestamp2 =  $request->route('timestamp2');

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;



        // if ($timestamp1 === null && $timestamp2 === null) {
        //     // $baseQuery = $this->etudiantAbsences()->where('annee_id', $currentYear->id);
        //     // if ($this->module_id !== null) {
        //     //     $baseQuery = $baseQuery->whereHas('seance', function ($query) {

        //     //         $query->where('module_id', $this->module_id);
        //     //     });
        //     // }

        //     // $studentAbsences = $baseQuery->get();
        //     $missingHours = $this->missedHoursSum;

        // } else if ($timestamp1 !== null && $timestamp2 !== null) {

        //     // $studentAbsences = $this->etudiantAbsences()->whereHas('seance', function ($query) use ($timestamp1, $timestamp2) {
        //     //     if ($this->module_id !== null) {
        //     //         $query = $query->where('module_id', $this->module_id);
        //     //     }

        //     //     $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
        //     // })->get();

        //     // $studentAbsences = $this->etudiantAbsences;


        // } else if ($timestamp1 !== null && $timestamp2 === null) {

        //     // $studentAbsences = $this->etudiantAbsences()->whereHas('seance', function ($query) use ($timestamp1) {

        //     //     if ($this->module_id !== null) {
        //     //         $query = $query->where('module_id', $this->module_id);
        //     //     }
        //     //     $query->where('heure_debut', '>', $timestamp1);
        //     // })->get();
        // }


        // $missingHours = $classeService->getStudentMissedHours($studentAbsences);



        
        $missingHours = $this->missedHoursSum;

        // $absencePercentage = ($missingHours * 100) / $this->nbre_heure_effectue;

        $presencePercentage = $studentService->AttendancePercentageCalc($missingHours, $this->nbre_heure_effectue);




        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'attendanceRate' => $presencePercentage,
            'picture' => $this->picture,
        ];
    }
}
