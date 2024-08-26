<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    


    private $currentYear=null;
    private $seance= null ;

    public function setSeance($seance) {
        $this->seance = $seance;
        return $this;
    }
    public function setCurrentYear($currentYear) {
        $this->currentYear = $currentYear;
        return $this;

    }
    public function toArray(Request $request)
    {
        return $this->collection->map(function($user){
            return new UserResource($user, $this->seance,$this->currentYear) ;
        });
    }

   



}
