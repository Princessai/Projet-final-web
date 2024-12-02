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

         

        $collection = $this->collection->map(function($seance){
          
            return  new SeanceResource($seance);
        })
        ->groupBy(function ( $item, int $key): string {
            
            $heure_fin = Carbon::parse($item['heure_fin'])->addMinutes(10);
            // apiSuccess($heure_fin)->send();
            // die();
            if($heure_fin >= $this->currentTime) {
                return 'comming';
            }
      
            return 'passed';
        });
       if(!$collection->has('comming')){
        $collection->put('comming', []);
       }

       if(!$collection->has('passed')){
        $collection->put('passed', []);
       }

       return $collection;

    }
}
