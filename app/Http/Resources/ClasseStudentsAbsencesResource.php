<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\Annee;
use Illuminate\Http\Request;
use App\Services\ClasseService;
use App\Services\StudentService;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseStudentsAbsencesResource extends JsonResource
{
    private $nbre_heure_effectue;

    public function __construct($resource, $nbre_heure_effectue)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;

        $this->nbre_heure_effectue = $nbre_heure_effectue;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array

    {
        $classeService = new ClasseService;
        $studentService = new StudentService;
        $annee = Annee::latest()->first();
        $timestamp1 =  $request->route('timestamp1');
        $timestamp2 =  $request->route('timestamp2');

        $timestamp1 = ($timestamp1 !== null) ? Carbon::createFromTimestamp($timestamp1)->toDateTimeString() : null;
        $timestamp2 = ($timestamp2 !== null) ? Carbon::createFromTimestamp($timestamp2)->toDateTimeString() : null;



        if ($timestamp1 === null && $timestamp2 === null) {
            $studentAbsences = $this->etudiantAbsences()->where('annee_id', $annee->id)->get();
        } else if ($timestamp1 !== null && $timestamp2 !== null) {

            $studentAbsences = $this->etudiantAbsences()->whereHas('seance', function ($query) use ($timestamp1, $timestamp2) {
                $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
            })->get();
        } else if ($timestamp1 !== null && $timestamp2 === null) {

            $studentAbsences = $this->etudiantAbsences()->whereHas('seance', function ($query) use ($timestamp1) {
                $query->where('heure_debut', '>', $timestamp1);
            })->get();
        }


        $missingHours = $classeService->getStudentMissedHours($studentAbsences);

        // $absencePercentage = ($missingHours * 100) / $this->nbre_heure_effectue;

        $presencePercentage = $studentService->percentageCalc($missingHours, $this->nbre_heure_effectue);




        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'attendanceRate' => $presencePercentage,
            'picture' => $this->picture,
            $timestamp1,
            $timestamp2
        ];
    }
}
