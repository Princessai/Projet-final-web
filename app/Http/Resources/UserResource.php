<?php

namespace App\Http\Resources;

use App\Models\Annee;
use App\Enums\roleEnum;
use App\Services\SeanceService;
use Illuminate\Http\Request;
use App\Enums\attendanceStateEnum;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\ModuleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    private $seance;
    private $currentYear;

    public function __construct($resource, $seance = null, $currentYear = null)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->seance = $seance;

        $this->currentYear = $currentYear;
    }



    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array

    {
        $isStudent = $this->role->label == roleEnum::Etudiant->value;
        $isParent = $request->user()->role->label == roleEnum::Parent->value;
        $isSeanceSet = !is_null($this->seance);



        return [

            "id" => $this->id,
            "name" => $this->name,
            "lastname" => $this->lastname,
            "picture" => $this->picture,
            "phone_number" => $this->phone_number,
            "email" => $this->email,
            "parent_id" => $this->when($isStudent&& !$this->relationLoaded('etudiantParent'), $this->parent_id),

            "parent"=> new UserResource ($this->whenLoaded('etudiantParent')),
            
            "role" => $this->when($this->relationLoaded('role') && $this->seance == null, $this->role),
            "classe" => $this->when($this->relationLoaded('etudiantsClasses'), function () {

                $annee_id = $this->currentYear->id;
                $etudiantCurrentClasse = $this->etudiantsClasses()->wherePivot('annee_id', $annee_id)->first();
                return new ClasseResource($etudiantCurrentClasse);
            }),
            "enseignantModules" =>  ModuleResource::collection($this->whenLoaded('enseignantModules')),
            "attendanceStatus" => $this->when(
                $isSeanceSet && $this->seance->relationLoaded('absences') && $this->seance->relationLoaded('delays'),
                function () {
                    $annee_id = $this->currentYear->id;
                    $module_id = $this->seance->module_id;
                    $SeanceService = new SeanceService();

                   return $SeanceService->getStudentAttendanceStatus($this->id, $this->seance->absences, $this->seance->delays);
                    
                    // $attendanceStatus = attendanceStateEnum::Present->value;

                    // $isAbsente = $this->seance->absences->contains(function ($absence,  $key) {
                    //     return $absence->user_id == $this->id;
                    // });

                    // if ($isAbsente) {
                    //     return $attendanceStatus = attendanceStateEnum::Absent->value;
                    // }

                    // $isLate = $this->seance->delays->contains(function ($absence,  $key) {

                    //     return $absence->user_id == $this->id;
                    // });

                    // if ($isLate) {
                    //     return $attendanceStatus = attendanceStateEnum::Late->value;
                    // }

                    // return $attendanceStatus;
                }
            ),
            "isDropped" => $this->when($isSeanceSet && $this->seance->relationLoaded('module') && $this->seance->module->relationLoaded('droppedStudents'), function () {
                $annee_id = $this->currentYear->id;
                $module_id = $this->seance->module_id;

                $this->seance->module
                    ->droppedStudents
                    ->contains(function ($droppedStudent,  $key) {
                        return $droppedStudent->user_id == $this->id && $droppedStudent->annee_id == $this->currentYear->id;
                    });

                return $etudiantCurrentClasse = $this->droppedStudentsModules()
                    ->wherePivot('annee_id', $annee_id)
                    ->where('modules.id', $module_id)->exists();
            })



        ];
    }
}
