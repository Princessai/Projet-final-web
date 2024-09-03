<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsenceResource extends JsonResource
{


    /**
     * Indicates if the resource's collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = true;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return  [


            "id" => $this->id,
            "etat" => $this->etat,
            "type_seance" => $this->when($this->relationLoaded('seance') && $this->seance->relationLoaded('typeSeance'), $this->seance->typeSeance),
            "coordinateur_id" => $this->coordinateur_id,
            "seance_heure_debut"=>$this->seance_heure_debut,
            "seance_heure_fin"=>$this->seance_heure_fin,
            "created_at" => $this->created_at,
            "annee_id" => $this->annee_id,

        ];
    }
}
