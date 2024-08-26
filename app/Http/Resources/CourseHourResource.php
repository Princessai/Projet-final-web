<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseHourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=>$this->id,
            "type"=>$this->type ,
            "number"=>$this->number,
            "start"=>$this->start ,
            "end"=>$this->end ,
            "annee_id"=>$this->annee_id,
            "workedHours"=>$this->workedHours , 
           
        ];
    }
}
