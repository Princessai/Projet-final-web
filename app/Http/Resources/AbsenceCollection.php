<?php

namespace App\Http\Resources;

use App\Models\Seance;
use Illuminate\Http\Request;
use App\Enums\absenceStateEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AbsenceCollection extends ResourceCollection
{
        public $preserveKeys = true;
    public function toArray(Request $request)
    {
       
        return $this->collection->map(function($absence){
        
            return new AbsenceResource($absence);
        })->groupBy(function ( $item, int $key) {
        
                $abscenceCase= absenceStateEnum::tryFrom($item['etat']);
                return  strtolower($abscenceCase->name);
            });

   
    }
}
