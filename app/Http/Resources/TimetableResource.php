<?php

namespace App\Http\Resources;

use App\Services\StudentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

function compareDebut($a, $b)
{
    // Convert debut times to Carbon instances
    $aDebut = $a['debut'];
    $bDebut = $b['debut'];
    $timeA = Carbon::createFromTimeString("$aDebut");
    $timeB = Carbon::createFromTimeString("$bDebut");

    // Compare the times
    return $timeA->timestamp - $timeB->timestamp;
}

class TimetableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pauses = require(base_path('data/pauses.php'));

        usort($pauses, 'app\Http\Resources\compareDebut');

        $StudentService = new StudentService;

        return [
            'id' => $this->id,
            'pauses' => $this->whenLoaded('seances',$pauses),
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'commentaire' => $this->commentaire,
            'classe_id' => $this->classe_id,
            'seances' => SeanceResource::collection($this->whenLoaded('seances')),
            'attendanceRate'=> $this->when($this->missedHoursSum 
            && $this->workedHoursSum,function() use ($StudentService){
                return $StudentService->AttendancePercentageCalc($this->missedHoursSum, $this->workedHoursSum);

            }, null),
        ];
    }
}
