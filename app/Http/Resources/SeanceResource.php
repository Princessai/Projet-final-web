<?php

namespace App\Http\Resources;

use App\Models\Annee;
use App\Models\Salle;
use App\Models\Module;
use App\Models\TypeSeance;
use Illuminate\Http\Request;
use App\Http\Resources\AnneeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SeanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return [$this->relationLoaded('enseignant')];
        return [



            "id" => $this->id,
            "etat" =>  $this->etat,
            "date" => $this->date,
            "attendance" => $this->attendance,
            "heure_debut" =>  $this->heure_debut,
            "heure_fin" =>  $this->heure_fin,
            "duree" =>  $this->duree,
            "duree_raw" =>  $this->duree_raw,
            "salle" =>  $this->salle,
            "module" => new ModuleResource($this->whenLoaded('module')),

            "manager" => $this->whenLoaded('enseignant', function () {
                return ["id" => $this->enseignant->id, "name" => $this->enseignant->name, "lastname" => $this->enseignant->lastname];
            }),

            "timetable_id" =>  $this->timetable_id,
            "type_seance" => $this->typeSeance,
            "classe" => new ClasseResource($this->whenLoaded('classe')),
            "annee_id" => $this->annee_id

        ];
    }
}
