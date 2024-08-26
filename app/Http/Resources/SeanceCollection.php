<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SeanceCollection extends ResourceCollection

{


    private $currentTime;
    public function setCurrentTime(Carbon $time){
        $this->currentTime = $time;
    }
 
    public function toArray(Request $request)
    {



        return $this->collection->map(function($seance){
          
            return  new SeanceResource($seance);
        })
        ->groupBy(function ( $item, int $key) {
           
            if($item['heure_fin'] >= $this->currentTime) {
                return 'comminnning';
            }
      
            return 'passed';
        });

    }
}
