<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Annee;
use App\Models\Salle;
use App\Models\Classe;
use App\Models\Module;
use App\Models\Seance;
use App\Models\Timetable;
use App\Models\TypeSeance;
use Carbon\CarbonImmutable;
use App\Enums\seanceStateEnum;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TimetableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $days = require(base_path('data/weekDays.php'));
        $annee_scolaire_id = Annee::latest()->first()->id;
        $classes = Classe::with(['modules.enseignants.enseignantClasses','coordinateur'])->get();
        $now = CarbonImmutable::now();
        $typeseances=TypeSeance::all();
        $salles=Salle::all();
        // $startOfDay = $now->startOfDay();
        // $dayOfWeekIndex = $startOfDay->dayOfWeek;
        // $dayOfWeekIndex = ($dayOfWeekIndex + 6) % 7;

        // if ($dayOfWeekIndex > 4) {
        //     $dayOffset = 7 - $dayOfWeekIndex;
        //     $date_debut =  $startOfDay->addDays($dayOffset);

        // } else {
        //     $date_debut =  $startOfDay->subDays($dayOfWeekIndex);

        // }
        $date_debut = $now->startOfWeek()->startOfDay();
        dump('start_of_week',$date_debut->toString());

        $date_fin = $date_debut->addDays(4)->endOfDay();
        dump('start_of_week',$date_debut->toString());


       

        $pauses = [
            ["name" => "midi", "debut" => 12, "fin" => 14,"isIncluded" => false],
            ["name" => "recreation", "debut" => "10:45", "fin" => 11,"isIncluded" => true]
        ];


        function compareDebut($a, $b)
        {
            // Convert debut times to Carbon instances
            $aDebut = $a['debut'];
            $bDebut = $b['debut'];
            $timeA = Carbon::createFromTimeString("$aDebut");
            $timeB = Carbon::createFromTimeString("$bDebut");

            // Compare the times
            return $timeA->timestamp - $timeB->timestamp;
        }

        // Sort the array
        usort($pauses, 'Database\Seeders\compareDebut');


      


        foreach ($classes as $classe) {
            $timetable_offset =  $date_debut->subMonths(1)->startOfWeek(Carbon::MONDAY);
            $timetable_offset_timestamp =  $timetable_offset->timestamp;
            dump('new_classe_______________________***');
            while ($timetable_offset_timestamp <= $date_fin->timestamp) {
                dump('new_timetable_______________________***');
                $timetableStart = $timetable_offset->copy();
                $daysPerWeek = 4;
                $timetableEnd = $timetableStart->addDays($daysPerWeek)->endOfDay();
                $timetableDayCount = 0;
               
                $timetable = Timetable::factory()->create([
                    'classe_id' => $classe->id,
                    'annee_id' => $annee_scolaire_id,
                    'date_debut' => $timetableStart,
                    'date_fin' =>  $timetableEnd,
                ]);
                while ($timetableDayCount <= $daysPerWeek) {
                    // dump("new_timetable______Dayy_________{day:$timetableDayCount}________*** \n\n\n");
                    $dayStart = 9;
                    $dayEnd = 17;
                    // $dayDuration=$dayEnd-$dayStart;
                    $dayDuration = 6;
                    $dayStep = 1;
                    $dayAvailableHours = $dayDuration;
                    $currentDay=$timetableStart->addDays($timetableDayCount);
                    $timetableDayStart = $currentDay->addHours($dayStart);
                    $seanceCount = rand(1, 2);
                    dump("seances____of_theday:{$timetableDayCount} seancecount:{$seanceCount} ");
                    // $seanceRandomHours=[];
                    $prevSeanceEnd = $timetableDayStart->copy();
                    $dstrt=$prevSeanceEnd->toString();
                    dump("days___start_9h:{$dstrt } \n");
                    for ($i = 1; $i <= $seanceCount; $i++) {
                        
    
                        if ($seanceCount == 1) {
                           
                            $seanceRandomHoursCount = $dayDuration;
                            dump("_only_one_ seance_duration number {$i}:{$seanceRandomHoursCount}");
                        }else{

                            if ($seanceCount != 1 && $i == $seanceCount) {
                                $seanceRandomHoursCount = $dayAvailableHours;
                                dump("__last__seance_duration number {$i}:{$seanceRandomHoursCount}");
    
                            } else {
                                $seanceRandomHoursCount = rand($dayStep, $dayAvailableHours - (($seanceCount - $i) * $dayStep));
                            
                                $dayAvailableHours -= $seanceRandomHoursCount;
                                dump("seance_duration number {$i}:{$seanceRandomHoursCount}");
                                // $seanceRandomHours[]=$seanceRandomHoursCount;
        
                            }
                        }
    
                   
    
                        $salle_id=$salles->random()->id;
                        $randomTypeseances= $typeseances->random();
                        $classeModuleRandom=$classe->modules->random();
                        if($randomTypeseances->label=="presentiel"){
                            
                            dump($classe->label);
                            // dump($classeModuleRandom->label);
                            $seanceManager=$classeModuleRandom->enseignants()->whereHas('enseignantClasses', function ($query) use($classe) {
                                $query->where('classes.id',$classe->id );
                            })->first();


                            
                            // dump($seanceManager->name);
                        }else{
                            $seanceManager=$classe->coordinateur;

                          

                        }
                        $seanceStates =seanceStateEnum::cases();
                        $randomSeanceState=fake()->randomElement($seanceStates)->value;


                        $currentSeanceStart = $prevSeanceEnd->copy();

                        if($seanceCount==$i){
                            $currentSeanceEnd = $currentDay->addHours($dayEnd);
                        }else{
                            $currentSeanceEnd = $currentSeanceStart->addHours($seanceRandomHoursCount);
                        }
                       
                        dump(" before__ split seance:{$i}    seance_start_attt  ".$currentSeanceStart->toString());
                        dump(" before__ split  seance:{$i}    seance_enddddd_attt  ".$currentSeanceEnd->toString());
    
                        $newSeanceStart = $currentSeanceStart->copy();
                        $newSeanceEnd = $currentSeanceEnd->copy();
    
                        $count = 0;
                        dump('start_foreach_pauses_____');
                        $noDiff=false;
                        foreach ($pauses as  $pause) {
    
                            $pauseDebut = $pause['debut'];
                            $isIncluded=$pause['isIncluded'];
                            $parsedTime = Carbon::createFromTimeString("$pauseDebut");
                            $pauseDebutHours = $parsedTime->hour;
                            // dump($pause['name']);
                            // dump('$pauseDebutHours', $pauseDebutHours);
                            $pauseDebutMinutes = $parsedTime->minute;
    
    
                            $pauseEnd = $pause['fin'];
                            
                            // dump('$pauseEnd' ,$pauseEnd);
                            $parsedTime = Carbon::createFromTimeString("$pauseEnd");
                            $pauseEndHours = $parsedTime->hour;
                            // dump('$pauseEndHours',$pauseEndHours);
                            $pauseEndMinutes = $parsedTime->minute;
    
                            $pauseDebut = $currentDay->addHours($pauseDebutHours)->addMinutes($pauseDebutMinutes);
    
                            $pauseEnd = $currentDay->addHours($pauseEndHours)->addMinutes($pauseEndMinutes);
    
                            // dump( 'pause',$pauseDebut->toString(), 'end',$pauseEnd->toString());
    
                            if ($newSeanceStart->lessThan($pauseEnd) && $newSeanceEnd->greaterThanOrEqualTo($pauseEnd)) {
                                

                                if($newSeanceEnd->greaterThanOrEqualTo($pauseEnd)){
                                    dump('greater_than');

                                    if(!$isIncluded){
                                        // si la seance commence avant la fin de la pause mais ne commence pas avant sont de debut(la seance commence en plein milieux de la pause)
                                            if($newSeanceStart->greaterThanOrEqualTo($pauseDebut)){
                                                $deltaThours=$pauseEnd->diffInHours($newSeanceStart,absolute:true);
                                            }else if($newSeanceStart->lessThan($pauseDebut)){
                                                $deltaThours=$pauseEnd->diffInHours($pauseDebut,absolute:true);
                                            }
                                        
                                        $deltaHours2=$newSeanceEnd->diffInHours($pauseEnd,absolute:true);
                                        $afterBreakSeanceEnd=$pauseEnd->addHours($deltaThours)->addHours($deltaHours2);

                                        $seance=Seance::factory()->create([
                                            'salle_id'=>$salle_id,
                                            'etat'=>$randomSeanceState,
                                            "date"=>$currentDay,	
                                            "heure_debut"=>$pauseEnd,	
                                            "heure_fin"=>$afterBreakSeanceEnd,
                                            "module_id"=>$classeModuleRandom->id,
                                            "classe_id"=>$classe->id,
                                            "annee_id"=>$annee_scolaire_id,
                                            "user_id"=> $seanceManager->id,	
                                            "timetable_id"=>$timetable->id,	
                                            "type_seance_id"=>$randomTypeseances->id]);

                                        // dump('delta___hours',$deltaThours);
                                        // dump($pauseEnd->toString(),$newSeanceStart->toString());
                                        // $deltaTminutes=$pauseEnd->diffInHours($newSeanceStart);
                                        
                                    }
                                }

                                /*creation d'une nouvelle seance  avec comme debut le debut initial et comme fin la fin de la pause*/
                                if($newSeanceStart->lessThan($pauseDebut)){
                                    // $newSeanceEnd = $pauseDebut->copy();
                                
                                    // $newSeanceStart
                                    
                                        $seance=Seance::factory()->create([
                                        'salle_id'=>$salle_id,
                                        'etat'=>$randomSeanceState,
                                        "date"=>$currentDay,	
                                        "heure_debut"=>$newSeanceStart,	
                                        "heure_fin"=>$pauseDebut,
                                        "module_id"=>$classeModuleRandom->id,
                                        "classe_id"=>$classe->id,
                                        "annee_id"=>$annee_scolaire_id,
                                        "user_id"=> $seanceManager->id,	
                                        "timetable_id"=>$timetable->id,	
                                        "type_seance_id"=>$randomTypeseances->id]);
                                    
                                }
                              
                                    // $diff=$currentSeanceEnd->diff($pauseEnd);
                                    /*si seance commence avant la fin de pause et finis apres le fin de la pause*/
                                   





                                // $diff=$currentSeanceEnd->timestamp - $pauseEnd->timestamp;
                                // if( $diff==0){
                                //     $noDiff=true;
                                // }
                                $pausename=$pause['name'];
                                // dump( "difference between  the original end and the end of the pause $pausename { $diff }",);
                        
                                /* creation*** pas vraiment creer d"une seance ayant comme debut le debut de la pause et comme fin la fin initiale */
                            
                                // $newSeanceStart = $pauseEnd->copy();
                                /*fin initiale de la seance*/
                                $newSeanceEnd = $afterBreakSeanceEnd->copy();
                            }
                            $count++;
                            if ($count == count($pauses)) {
                                dump('end________',$count == count($pauses),$count,count($pauses));
                                $currentSeanceStart = $newSeanceStart->copy();
                                $currentSeanceEnd = $newSeanceEnd->copy();
                            }
                        }












                        
                        dump(" after split  seance:{$i}    seance_enddddd_attt  ".$newSeanceEnd->toString());
                        
                            /* create a seance */
                            // if(!$noDiff){
                            //     $seance=Seance::factory()->create(['salle_id'=>$salle_id,'etat'=>$randomSeanceState,"date"=>$currentDay,	"heure_debut"=>$newSeanceStart,	"heure_fin"=>$newSeanceEnd,"module_id"=>$classeModuleRandom->id,"classe_id"=>$classe->id,"annee_id"=>$annee_scolaire_id,"user_id"=> $seanceManager->id,	"timetable_id"=>$timetable->id,	"type_seance_id"=>$randomTypeseances->id]);
                            // }
                            // dump('____________________________________________');
                            // $randomTypeseances= $typeseances->random()->label;
                            // dump($randomTypeseances);
                            // if($randomTypeseances=="presentiel"){
                            //     $classeModuleRandom=$classe->modules->random();
                            //     dump($classe->label);
                            //     dump($classeModuleRandom->label);
                            //     $randomModuleTeacher=$classeModuleRandom->enseignants()->whereHas('enseignantClasses', function ($query) use($classe) {
                            //         $query->where('classes.id',$classe->id );
                            //     })->first();
                                
                            //     dump($randomModuleTeacher->name);
                            // }else{
                            //     dump('coordinateur',$classe->coordinateur->id,$classe->coordinateur->name);


                            // }
                          
    
    
    
    
    
    
            
    
    
                        $prevSeanceEnd = $currentSeanceEnd->copy();
                    }
             
    
    
                    $timetableDayCount++;
                }
    
                $timetable_offset = $timetable_offset->addWeek();
                $timetable_offset_timestamp = $timetable_offset->timestamp;
                dump('***************************');
                dump('classlabel',$classe->label);
                dump('$timetable_offset_timestamp inloop', $timetable_offset->toString());
            }
         
        }


  
    }
}



//  $timetable = Timetable::factory()->create([
//                 'classe_id' => $classe->id,
//                 'annee_id' => $annee_scolaire_id,
//                 'date_debut' => $date_debut,
//                 'date_fin' =>  $date_fin,
//             ]);
//         Create a Carbon instance for the desired date

//         dump($date_fin->toDateTimeString());
//         dump($dayName);
//         $nowWeek = $now->week();

//         return [
//             'annee_id' => $annee_scolaire_id,
//             'date_debut' => $date_debut,
//             'date_fin' =>  $date_fin,
//         ];
    //         // function intersectWithPause($interval1, $interval2, $pauses, $index = 0,)
                //         // {
                //         //     if (count($pauses) == $index) return;
    
                //         //     if ($interval1['start']->lessThan($interval2['start']) && $interval1['end']->greaterThan($interval2['end'])) {
                //         //         $interval1EndCopy = $interval1['end']->copy();
                //         //         $interval1StartCopy = $interval1['start']->copy();
                //         //         $interval1['end'] = $interval2['start'];
                //         //         /* new seance $interval1StartCopy to $interval1['end']=$interval2['start']*/
                //         //         /*seconde interval */
                //         //         $interval1['start'] = $interval2['end'];
                //         //     }
                //         // }

                   //     // rsort($seanceRandomHours);
                //     // $prevSeanceStart=$timetableDayStart;
                //     // $prevSeanceEnd;
                //     // foreach ($seanceRandomHours as $seanceRandomHour) {
    
    
    
                //     //     $prevSeanceStart=$timetableDayStart;
                //     //     $prevSeanceEnd=$timetableDayStart->addHours($seanceRandomHour);
                //     // }