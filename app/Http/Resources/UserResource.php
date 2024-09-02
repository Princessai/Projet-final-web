<?php

namespace App\Http\Resources;

use App\Models\Annee;
use App\Enums\roleEnum;
use Illuminate\Http\Request;
use App\Services\SeanceService;
use App\Enums\attendanceStateEnum;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\ModuleResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    private $seance;
    private $currentYearId;
    private $roleLabel;

    public function __construct($resource, $seance = null, $currentYearId = null, $roleLabel = null)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->seance = $seance;

        $this->currentYearId = $currentYearId;
        $this->roleLabel = $roleLabel;
    }



    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array

    {
        $isStudent = $this->roleLabel == roleEnum::Etudiant->value;


        $isSeanceSet = !is_null($this->seance);



        return [

            "id" => $this->id,
            "name" => $this->name,
            "lastname" => $this->lastname,
            "picture" => $this->picture,
            "phone_number" => $this->phone_number,
            "email" => $this->email,
            "parent_id" => $this->when($isStudent && !$this->relationLoaded('etudiantParent'), $this->parent_id),

            "parent" => new UserResource($this->whenLoaded('etudiantParent'), roleLabel: roleEnum::Etudiant->value),

            "role" => $this->when($this->relationLoaded('role') && $this->seance == null, function (){
               return $this->role;
            }),
            "classe" => $this->when($this->relationLoaded('etudiantsClasses'), function () {

                $etudiantCurrentClasse = $this->etudiantsClasses;

                if ($etudiantCurrentClasse instanceof Collection) {
                    return ClasseResource::collection($etudiantCurrentClasse);
                } else if ($etudiantCurrentClasse instanceof Model) {
                    return new ClasseResource($etudiantCurrentClasse);
                }
            }),
            "enseignantModules" =>  ModuleResource::collection($this->whenLoaded('enseignantModules')),
            "attendanceStatus" => $this->when(
                $isSeanceSet && $this->seance->relationLoaded('absences') && $this->seance->relationLoaded('delays'),
                function () {

                    $SeanceService = new SeanceService();

                    return $SeanceService->getStudentAttendanceStatus($this->id, $this->seance->absences, $this->seance->delays);
                }
            ),
            "isDropped" => $this->when($isSeanceSet && $this->seance->relationLoaded('module') && $this->seance->module->relationLoaded('droppedStudents'), function () {


                return $this->seance->module
                    ->droppedStudents
                    ->contains(function ($droppedStudent,  $key) {
                        return $droppedStudent->id == $this->id;
                    });
            })



        ];
    }
}
