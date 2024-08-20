<?php

namespace App\Http\Resources;

use App\Models\Annee;
use App\Enums\roleEnum;
use Illuminate\Http\Request;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\ModuleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array

    {
        $isStudent=$request->user()->role->label==roleEnum::Etudiant->value;
        $isParent=$request->user()->role->label==roleEnum::Parent->value;

        return [

            "id" => $this->id,
            "name" => $this->name,
            "lastname" => $this->lastname,
            "picture" => $this->picture,
            "phone_number" => $this->phone_number,
            "email" => $this->email,
            "parent_id" => $this->whenNotNull($this->parent_id) ,
            "role" => $this->role,
            "classe"=>$this->when($this->relationLoaded('etudiantsClasses'), function () {
                $annee_id=Annee::latest()->first()->id;
                $etudiantCurrentClasse=$this->etudiantsClasses()->wherePivot('annee_id', $annee_id)->first();
                return new ClasseResource($etudiantCurrentClasse);
            }),
            "enseignantModules"=>  ModuleResource::collection($this->whenLoaded('enseignantModules')) 
          


        ];
    }
}
