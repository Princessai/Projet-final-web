<?php
namespace App\Services;

use App\Models\Annee;




class AnneeService{
    public $currentYear;
    public  function __construct($annee_id=null) {
        if($annee_id==null){
            $this->currentYear = Annee::latest()->first();
        }
       
      }

    public function getCurrentYear(){
        return $this->currentYear;
    }
 

}
