<?php

namespace App\Http\Resources;

use App\Models\Annee;
use App\Models\Salle;
use App\Models\Module;
use App\Models\Typeseance;
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
        return [
            'id' => $this->id,
            "etat" => $this->etat,
            "date" => $this->date,
            "attendance" => $this->attendance,
            "heure_debut" => $this->heure_debut,
            "heure_fin" => $this->heure_fin,
            "duree" => $this->duree,
            "salle" => Salle::find($this->salle_id) ,
            "module" => Module::find($this->module_id) ,
            "user_id" => $this->user_id,
            "typeseance" => Typeseance::find($this->typeseance_id) ,
            "classe_id" => $this->classe_id,
            "annee" =>  new AnneeResource(Annee::find($this->annee_id)) 
        ];
    }
}
