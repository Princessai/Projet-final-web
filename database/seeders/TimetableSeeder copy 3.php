<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Annee;
use App\Models\Salle;
use App\Models\Classe;
use App\Models\Module;
use App\Models\Seance;
use App\Models\Timetable;
use App\Models\Typeseance;
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
        $classes = Classe::with(['modules.enseignants.enseignantClasses', 'coordinateur'])->get();
        $now = CarbonImmutable::now();
        $typeseances = Typeseance::all();
        $salles = Salle::all();
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


        $date_fin = $date_debut->addDays(4)->endOfDay();




        $pauses = [
            ["name" => "midi", "debut" => 12, "fin" => 14, "isIncluded" => false],
            ["name" => "recreation", "debut" => "10:45", "fin" => 11, "isIncluded" => true],
            ["name" => "aprem", "debut" => "15:45", "fin" => 16, "isIncluded" => true],
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
            $timetable_offset =  $date_debut->startOfWeek(Carbon::MONDAY);
            $timetable_offset_timestamp =  $timetable_offset->timestamp;
            dump('new_classe***************');
            dump('classlabel', $classe->label);

            while ($timetable_offset_timestamp <= $date_fin->timestamp) {
                dump('***************************');
                dump('$timetables_start:', $timetable_offset->toString());
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
                    $dayStart = 9;
                    $dayEnd = 17;
                    // $dayDuration=$dayEnd-$dayStart;
                    $dayDuration = 6;
                    $dayStep = 1;
                    $dayAvailableHours = $dayDuration;
                    $currentDay = $timetableStart->addDays($timetableDayCount);
                    $timetableDayStart = $currentDay->addHours($dayStart);
                    // $seanceCount = rand(2, 4);
                    $seanceCount = 2;
                    dump("random seance num_of_the day:{$timetableDayCount} seancecount:{$seanceCount}");
                    // $seanceRandomHours=[];
                    $prevSeanceEnd = $timetableDayStart->copy();

                    for ($i = 1; $i <= $seanceCount; $i++) {


                        if ($seanceCount == 1) {

                            $seanceRandomHoursCount = $dayDuration;
                            dump("_only_one_ seance_duration {$i} duration: {$seanceRandomHoursCount}");
                        } else {

                            if ($seanceCount != 1 && $i == $seanceCount) {
                                // $seanceRandomHoursCount = $dayAvailableHours;
                                $seanceRandomHoursCount = 4;

                                dump("__last__seance_ number {$i} duration:{$seanceRandomHoursCount}");
                            } else {
                                // $seanceRandomHoursCount = rand($dayStep, $dayAvailableHours - (($seanceCount - $i) * $dayStep));
                                $seanceRandomHoursCount = 2;

                                $dayAvailableHours -= $seanceRandomHoursCount;
                                dump("seance number  {$i} duration:{$seanceRandomHoursCount}");
                                // $seanceRandomHours[]=$seanceRandomHoursCount;

                            }
                        }



                        $salle_id = $salles->random()->id;
                        $randomTypeseances = $typeseances->random();
                        $classeModuleRandom = $classe->modules->random();

                        if ($randomTypeseances->label == "presentiel") {

                            dump($classe->label);
                            // dump($classeModuleRandom->label);
                            $seanceManager = $classeModuleRandom->enseignants()->whereHas('enseignantClasses', function ($query) use ($classe) {
                                $query->where('classes.id', $classe->id);
                            })->first();
                        } else {
                            $seanceManager = $classe->coordinateur;
                        }
                        $seanceStates = seanceStateEnum::cases();
                        $randomSeanceState = fake()->randomElement($seanceStates)->value;


                        $currentSeanceStart = $prevSeanceEnd->copy();
                        dump("previous seance start at " . $currentSeanceStart->toTimeString());

                        // if($seanceCount==$i){
                        //     $currentSeanceEnd = $currentDay->addHours($dayEnd);
                        // }else{

                        // }

                        $currentSeanceEnd = $currentSeanceStart->addHours($seanceRandomHoursCount);

                        // dump(" before__ split seance:{$i}   start_at  ".$currentSeanceStart->toString());
                        dump(" before__ split  seance:{$i}   enddd_at  " . $currentSeanceEnd->toTimeString());

                        $newSeanceStart = $currentSeanceStart->copy();
                        $newSeanceEnd = $currentSeanceEnd->copy();

                        $count = 1;
                        dump('pauses loop_start_____');
                        $intersectPause = false;
                        $lastIntersectingPauseEnd=null;
                        foreach ($pauses as  $pause) {

                            $pauseDebut = $pause['debut'];
                            $isIncluded = $pause['isIncluded'];
                            $parsedTime = Carbon::createFromTimeString("$pauseDebut");
                            $pauseDebutHours = $parsedTime->hour;

                            $pauseDebutMinutes = $parsedTime->minute;


                            $pauseEnd = $pause['fin'];

                            $parsedTime = Carbon::createFromTimeString("$pauseEnd");
                            $pauseEndHours = $parsedTime->hour;
                            // dump('$pauseEndHours',$pauseEndHours);
                            $pauseEndMinutes = $parsedTime->minute;

                            $pauseDebut = $currentDay->addHours($pauseDebutHours)->addMinutes($pauseDebutMinutes);

                            $pauseEnd = $currentDay->addHours($pauseEndHours)->addMinutes($pauseEndMinutes);





                            if ($newSeanceStart->lessThan($pauseEnd) && $newSeanceEnd->greaterThanOrEqualTo($pauseEnd)) {
                                
                                dump("pauses name: " . $pause['name']);
                                dump('comdition1');
                                dump($newSeanceStart->toTimeString());
                                dump($newSeanceEnd->toTimeString());
                                $intersectPause = true;
                                $lastIntersectingPauseEnd= $pauseEnd;
                                if ($newSeanceStart->lessThan($pauseDebut)) {
                                   
                                    dump("seance avant la pause"."debut: ".$newSeanceStart->toTimeString()." fin: ".$pauseDebut->toTimeString());
                                    $seance = Seance::factory()->create([
                                        'salle_id' => $salle_id,
                                        'etat' => $randomSeanceState,
                                        "date" => $currentDay,
                                        "heure_debut" => $newSeanceStart,
                                        "heure_fin" => $pauseDebut,
                                        "module_id" => $classeModuleRandom->id,
                                        "classe_id" => $classe->id,
                                        "annee_id" => $annee_scolaire_id,
                                        "user_id" => $seanceManager->id,
                                        "timetable_id" => $timetable->id,
                                        "typeseance_id" => $randomTypeseances->id
                                    ]);
                                }



                                if (!$isIncluded) {
                                    // si la seance commence avant la fin de la pause mais ne commence pas avant sont de debut(la seance commence en plein milieux de la pause)
                                    if ($newSeanceStart->greaterThanOrEqualTo($pauseDebut)) {
                                        $deltaThours = $pauseEnd->diffInHours($newSeanceStart, absolute: true);
                                    } else if ($newSeanceStart->lessThan($pauseDebut)) {
                                        $deltaThours = $pauseEnd->diffInHours($pauseDebut, absolute: true);
                                    }

                                    $deltaHours2 = $newSeanceEnd->diffInHours($pauseEnd, absolute: true);

                                    $afterBreakSeanceEnd = $pauseEnd->addHours($deltaThours)->addHours($deltaHours2);
                                    dump($pause['name']);
                                    dump($count);
                                    dump($pauseEnd->toTimeString());
                                        //     if ($count  == count($pauses)) {
                                        //         dump('end___***',$afterBreakSeanceEnd->toTimeString());
                                        //         $seance = Seance::factory()->create([
                                        //             'salle_id' => $salle_id,
                                        //             'etat' => $randomSeanceState,
                                        //             "date" => $currentDay,
                                        //             "heure_debut" => $pauseEnd,
                                        //             "heure_fin" => $afterBreakSeanceEnd,
                                        //             "module_id" => $classeModuleRandom->id,
                                        //             "classe_id" => $classe->id,
                                        //             "annee_id" => $annee_scolaire_id,
                                        //             "user_id" => $seanceManager->id,
                                        //             "timetable_id" => $timetable->id,
                                        //             "typeseance_id" => $randomTypeseances->id
                                        //         ]);
                                        // }
                                        
                                        if ($count  != count($pauses)){
                                            /*au prochain tour  de la boucle des pauses la fin de la seance sera
                                             egale a la portion de la seance courente en intersection avec
                                             la pause courante + la portion de la seance apres la pause pour le cas d'une pause non inclusive */
                                            $newSeanceEnd= $afterBreakSeanceEnd->copy();
                                              /*au prochain tour  de la boucle des pauses le debut de la seance sera
                                             egale a la fin de la pause courente(devenu pause predente au prochain tour) */
                                            $afterBreakSeanceEnd=$pauseEnd->copy();

                                        }

                                    // dump('delta___hours',$deltaThours);
                                    // dump($pauseEnd->toString(),$newSeanceStart->toString());
                                    // $deltaTminutes=$pauseEnd->diffInHours($newSeanceStart);

                                } else if ($isIncluded) {

                                    if( $count  != count($pauses)){
                                        $afterBreakSeanceEnd = $pauseEnd->copy();
                                    }
                                     if($count  == count($pauses)){
                                        /* au dernier tour de la boucle des pauses $afterBreakSeanceEnd sera utilisé pour determiner la fin de la seance courante  */
                                        $afterBreakSeanceEnd = $newSeanceEnd->copy();
                                    }

                                
                               
                                }


                                /*creation d'une nouvelle seance  avec comme debut le debut initial et comme fin la fin de la pause*/

                                // $diff=$currentSeanceEnd->diff($pauseEnd);
                                /*si seance commence avant la fin de pause et finis apres le fin de la pause*/






                             
                                $pausename = $pause['name'];
                                // dump( "difference between  the original end and the end of the pause $pausename { $diff }",);

                                /* creation*** pas vraiment creer d"une seance ayant comme debut le debut de la pause et comme fin la fin initiale */

                                // $newSeanceStart = $pauseEnd->copy();
                                /*fin initiale de la seance*/
                                $newSeanceStart = $afterBreakSeanceEnd->copy();
                            }

                            if ($newSeanceEnd->greaterThanOrEqualTo($pauseDebut) && $newSeanceEnd->lessThan($pauseEnd)) {
                                dump("pauses name: " . $pause['name']);
                                dump('comdition2');
                                dump($newSeanceStart->toTimeString());
                                dump($newSeanceEnd->toTimeString());
                                $intersectPause = true;
                                $lastIntersectingPauseEnd=$pauseEnd;
                                dump("seance avant la pause"."debut: ".$newSeanceStart->toTimeString()." fin: ".$pauseDebut->toTimeString());
                                $seance = Seance::factory()->create([
                                    'salle_id' => $salle_id,
                                    'etat' => $randomSeanceState,
                                    "date" => $currentDay,
                                    "heure_debut" => $newSeanceStart,
                                    "heure_fin" => $pauseDebut,
                                    "module_id" => $classeModuleRandom->id,
                                    "classe_id" => $classe->id,
                                    "annee_id" => $annee_scolaire_id,
                                    "user_id" => $seanceManager->id,
                                    "timetable_id" => $timetable->id,
                                    "typeseance_id" => $randomTypeseances->id
                                ]);




                                if (!$isIncluded) {
                                    dump('condition 2 pause non inclut');
                                    $deltaThours = $newSeanceEnd->diffInHours($pauseDebut, absolute: true);
                                    // dump($deltaThours);

                                    $afterBreakSeanceEnd = $pauseEnd->copy();
                                    if ($deltaThours != 0) {

                                        $afterBreakSeanceEnd = $afterBreakSeanceEnd->addHours($deltaThours);
                                        $seance = Seance::factory()->create([
                                            'salle_id' => $salle_id,
                                            'etat' => $randomSeanceState,
                                            "date" => $currentDay,
                                            "heure_debut" => $pauseEnd,
                                            "heure_fin" => $afterBreakSeanceEnd,
                                            "module_id" => $classeModuleRandom->id,
                                            "classe_id" => $classe->id,
                                            "annee_id" => $annee_scolaire_id,
                                            "user_id" => $seanceManager->id,
                                            "timetable_id" => $timetable->id,
                                            "typeseance_id" => $randomTypeseances->id
                                        ]);
                                    }
                                } else {
                                    $afterBreakSeanceEnd = $pauseEnd->copy();
                                }



                                $newSeanceStart = $afterBreakSeanceEnd->copy();
                            }

                         

                            if ($count == count($pauses)) {

                             
                                   
                            
                            

                                // $currentSeanceStart = $newSeanceStart->copy();
                                if ($intersectPause) {
                                    dump("last seance apres la pause"." debut: ".$pauseEnd->toTimeString()." fin: ".$afterBreakSeanceEnd->toTimeString());
                                    dump('last pause***',$afterBreakSeanceEnd->toTimeString());
                                if($lastIntersectingPauseEnd->diffInHours($afterBreakSeanceEnd,absolute:true)!=0){
                                    $seance = Seance::factory()->create([
                                        'salle_id' => $salle_id,
                                        'etat' => $randomSeanceState,
                                        "date" => $currentDay,
                                        "heure_debut" => $lastIntersectingPauseEnd,
                                        "heure_fin" => $afterBreakSeanceEnd,
                                        "module_id" => $classeModuleRandom->id,
                                        "classe_id" => $classe->id,
                                        "annee_id" => $annee_scolaire_id,
                                        "user_id" => $seanceManager->id,
                                        "timetable_id" => $timetable->id,
                                        "typeseance_id" => $randomTypeseances->id
                                    ]);
                                }
                                  
                                    $currentSeanceEnd = $afterBreakSeanceEnd->copy();
                                }
                            }
                            $count++;
                        }



                        if (!$intersectPause) {
                            dump("seance sans intersection"."debut: ".$newSeanceStart->toTimeString()." fin: ".$newSeanceEnd->toTimeString());

                            $seance = Seance::factory()->create([
                                'salle_id' => $salle_id,
                                'etat' => $randomSeanceState,
                                "date" => $currentDay,
                                "heure_debut" => $newSeanceStart,
                                "heure_fin" => $newSeanceEnd,
                                "module_id" => $classeModuleRandom->id,
                                "classe_id" => $classe->id,
                                "annee_id" => $annee_scolaire_id,
                                "user_id" => $seanceManager->id,
                                "timetable_id" => $timetable->id,
                                "typeseance_id" => $randomTypeseances->id
                            ]);
                        }
                        dump(" after split  seance:{$i}    seance_enddddd_attt  " . $newSeanceEnd->toString());
                        dump('pauses loop_end_____');














                        /* create a seance */
                        // if(!$noDiff){
                        //     $seance=Seance::factory()->create(['salle_id'=>$salle_id,'etat'=>$randomSeanceState,"date"=>$currentDay,	"heure_debut"=>$newSeanceStart,	"heure_fin"=>$newSeanceEnd,"module_id"=>$classeModuleRandom->id,"classe_id"=>$classe->id,"annee_id"=>$annee_scolaire_id,"user_id"=> $seanceManager->id,	"timetable_id"=>$timetable->id,	"typeseance_id"=>$randomTypeseances->id]);
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
                    return;
                }

                $timetable_offset = $timetable_offset->addWeek();
                $timetable_offset_timestamp = $timetable_offset->timestamp;
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