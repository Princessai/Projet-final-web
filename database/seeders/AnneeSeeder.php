<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Annee;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AnneeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = require(base_path('data/annee.php'));
        $yearsArr =[];
        $now =now();

        foreach( $years as $year){
         
            $yearStart= Carbon::parse($year['start']);
            $yearEnd= Carbon::parse($year['end']);
         
            $yearsArr[]=[
            'date_debut'=>$yearStart,
            'date_fin'=>$yearEnd,
            'annee_scolaire'=>"$yearStart->year - $yearEnd->year" ,
            "created_at"=> $now ,
            "updated_at"=>$now];
        }

        Annee::insert($yearsArr);
    }
}
