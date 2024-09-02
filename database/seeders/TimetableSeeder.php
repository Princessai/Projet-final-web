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

include_once(base_path('utilities\seeder\seanceDuration.php'));

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
        $typeseances = TypeSeance::all();
        $salles = Salle::all();

        $pauses = require(base_path('data/pauses.php'));
        // $startOfDay = $now->startOfDay();
        // $dayOfWeekIndex = $startOfDay->dayOfWeek;
        // $dayOfWeekIndex = ($dayOfWeekIndex + 6) % 7;

        // if ($dayOfWeekIndex > 4) {
        //     $dayOffset = 7 - $dayOfWeekIndex;
        //     $date_debut =  $startOfDay->addDays($dayOffset);

        // } else {
        //     $date_debut =  $startOfDay->subDays($dayOfWeekIndex);

        // }

        // function createSeance(
        //     $salle_id,
        //     $etat,
        //     $date,
        //     $heure_debut,
        //     $heure_fin,
        //     $duree,
        //     $module,
        //     $classe,
        //     $annee_id,
        //     $user,
        //     $timetable,
        //     $typeseance,
        //     $annee_scolaire_id
        // ) {

        //     $seance = Seance::factory()->createAbsentStudent($classe, $annee_scolaire_id)->create([
        //         'salle_id' => $salle_id,
        //         'etat' => $etat,
        //         "date" => $date,
        //         "heure_debut" => $heure_debut,
        //         "heure_fin" => $heure_fin,
        //         "duree" => $duree,
        //         "module_id" => $module->id,
        //         "classe_id" => $classe->id,
        //         "annee_id" => $annee_id,
        //         "user_id" => $user->id,
        //         "timetable_id" => $timetable->id,
        //         "type_seance_id" => $typeseance->id
        //     ]);
        // }



        function randomState($seance_end, $now)
        {

            if ($seance_end->greaterThanOrEqualTo($now)) {

                $randomSeanceState = seanceStateEnum::ComingSoon->value;
            } else {

                $seanceStates = seanceStateEnum::cases();
                $seanceStates = array_filter($seanceStates, fn($case) => $case !== seanceStateEnum::ComingSoon->value);

                $randomSeanceState = fake()->randomElement($seanceStates)->value;
                $randomSeanceState = 3;
            };

            return $randomSeanceState;
        }

       

        $date_debut = $now->startOfWeek()->startOfDay();
        dump('initial value of $date_debut ' . $date_debut);


        $date_fin = $date_debut->next(Carbon::MONDAY)->addDays(4)->endOfDay();
        dump('initial value of $date_fin ' . $date_fin);
        dump($classes->first()->id);
        // dump('$classeModuleRandom',

        // $classes->first()->modules->random()->pluck('id')->all());



        // $pauses = [
        //     ["name" => "aprem", "debut" => "15:45", "fin" => 16, "isIncluded" => true],
        //     ["name" => "midi", "debut" => 12, "fin" => 14, "isIncluded" => false],
        //     ["name" => "recreation", "debut" => "10:45", "fin" => 11, "isIncluded" => true],
        //     // ["name" => "essai", "debut" => "14:45", "fin" => 15, "isIncluded" => true],
        //     // ["name" => "essai2", "debut" => 15, "fin" => "15:15", "isIncluded" => true],
        //     // ["name" => "essai3", "debut" => 9, "fin" => "9:15", "isIncluded" => true],
        //     // ["name" => "essai3", "debut" => "16:30", "fin" => "16:45", "isIncluded" => false],

        // ];


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




        // foreach ($classes as $classe) {
        $timetable_offset =  $date_debut->subDays(2)->startOfWeek(Carbon::MONDAY);
        $timetable_offset_timestamp =  $timetable_offset->timestamp;


        while ($timetable_offset_timestamp <= $date_fin->timestamp) {
            dump('***************************');
            dump('$timetables_start:', $timetable_offset->toString());

            dump('new_timetable_______________________***');
            foreach ($classes as $classe) {
                dump('new_classe***************');

                dump('classlabel', $classe->label);
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
                    $seanceCount = rand(1, 3);
                    // $seanceCount = 4;
                    dump("random seance num_of_the day:{$timetableDayCount} seancecount:{$seanceCount}");
                    // $seanceRandomHours=[];
                    $prevSeanceEnd = $timetableDayStart->copy();

                    for ($i = 1; $i <= $seanceCount; $i++) {


                        if ($seanceCount == 1) {

                            $seanceRandomHoursCount = $dayDuration;
                            dump("_only_one_ seance_duration {$i} duration: {$seanceRandomHoursCount}");
                        } else {

                            if ($seanceCount != 1 && $i == $seanceCount) {
                                $seanceRandomHoursCount = $dayAvailableHours;
                                // $seanceRandomHoursCount = 2;


                                dump("__last__seance_ number {$i} duration:{$seanceRandomHoursCount}");
                            } else {
                                $seanceRandomHoursCount = rand($dayStep, $dayAvailableHours - (($seanceCount - $i) * $dayStep));
                                // /*just pour debug */

                                // $seanceRandomHoursCount = 4;
                                // if($i == 1||$i == 3) $seanceRandomHoursCount=1;
                                // if($i == 2) $seanceRandomHoursCount=2;

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
                        dump('value of $date_debut in loop ' . $date_debut);
                        dump('value of $timetable_offset in loop ' . $timetable_offset);
                        // if ($timetable_offset->greaterThanOrEqualTo($date_debut)) {

                        //     $randomSeanceState = seanceStateEnum::Defer->value;
                        // } else {
                        //     $seanceStates = seanceStateEnum::cases();
                        //     $randomSeanceState = fake()->randomElement($seanceStates)->value;
                        //     $randomSeanceState = 3;
                        // };




                        $currentSeanceStart = $prevSeanceEnd->copy();
                        dump("previous seance start at " . $currentSeanceStart->toTimeString());



                        $currentSeanceEnd = $currentSeanceStart->addHours($seanceRandomHoursCount);

                        dump(" before__ split  seance:{$i}   enddd_at  " . $currentSeanceEnd->toTimeString());

                        $newSeanceStart = $currentSeanceStart->copy();
                        $newSeanceEnd = $currentSeanceEnd->copy();

                        $count = 1;
                        dump('pauses loop_start_____');
                        $intersectPause = false;
                        $lastIntersectingPauseEnd = null;
                        $lastIntersectingPauseStart = null;
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
                                $lastIntersectingPauseEnd = $pauseEnd;
                                $lastIntersectingPauseStart = $pauseDebut;
                                if ($newSeanceStart->lessThan($pauseDebut)) {

                                    dump("seance avant la pause" . "debut: " . $newSeanceStart->toTimeString() . " fin: " . $pauseDebut->toTimeString());
                                    if ($pauseDebut->diffInHours($newSeanceStart, absolute: true) != 0) {
                                        // $duree = $pauseDebut->diffInHours($newSeanceStart, absolute: true);
                                        $duree = seanceDuration($pauseDebut, $newSeanceStart);
                                        $dureeraw = seanceDuration($pauseDebut, $newSeanceStart,false);
                                        dump('rawwww___',  $dureeraw);

                                        $randomSeanceState = randomState($pauseDebut, $now);
                                        $seance = Seance::factory()->createAbsentStudent($classe, $annee_scolaire_id)->create([
                                            'salle_id' => $salle_id,
                                            'etat' => $randomSeanceState,
                                            "date" => $currentDay,
                                            "heure_debut" => $newSeanceStart,
                                            "heure_fin" => $pauseDebut,
                                            "duree" => $duree,
                                            "duree_raw"=>$dureeraw,
                                            "module_id" => $classeModuleRandom->id,
                                            "classe_id" => $classe->id,
                                            "annee_id" => $annee_scolaire_id,
                                            "user_id" => $seanceManager->id,
                                            "timetable_id" => $timetable->id,
                                            "type_seance_id" => $randomTypeseances->id
                                        ]);
                                    }
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


                                    if ($count  != count($pauses)) {
                                        /*au prochain tour  de la boucle des pauses la fin de la seance sera
                                             egale a la portion de la seance courente en intersection avec
                                             la pause courante + la portion de la seance apres la pause pour le cas d'une pause non inclusive */
                                        $newSeanceEnd = $afterBreakSeanceEnd->copy();
                                        /*au prochain tour  de la boucle des pauses le debut de la seance sera
                                             egale a la fin de la pause courente(devenu pause predente au prochain tour) */
                                        $afterBreakSeanceEnd = $pauseEnd->copy();
                                    }

                                    // dump('delta___hours',$deltaThours);
                                    // dump($pauseEnd->toString(),$newSeanceStart->toString());
                                    // $deltaTminutes=$pauseEnd->diffInHours($newSeanceStart);

                                } else if ($isIncluded) {

                                    if ($count  != count($pauses)) {
                                        $afterBreakSeanceEnd = $pauseEnd->copy();
                                    }
                                    if ($count  == count($pauses)) {
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
                                $lastIntersectingPauseEnd = $pauseEnd;
                                $lastIntersectingPauseStart = $pauseDebut;
                                dump("seance avant la pause" . "debut: " . $newSeanceStart->toTimeString() . " fin: " . $pauseDebut->toTimeString());
                                if ($pauseDebut->diffInHours($newSeanceStart, absolute: true) != 0) {
                                    // $duree = ceil($seance->heure_debut->diffInHours($seance->heure_fin ,absolute:true)) ;
                                    $duree = seanceDuration($pauseDebut, $newSeanceStart);
                                    $dureeraw = seanceDuration($pauseDebut, $newSeanceStart,false);
                                    // $pauseDebut->diffInHours($newSeanceStart, absolute: true);
                                    $randomSeanceState = randomState($pauseDebut, $now);
                                    $seance = Seance::factory()->createAbsentStudent($classe, $annee_scolaire_id)->create([
                                        'salle_id' => $salle_id,
                                        'etat' => $randomSeanceState,
                                        "date" => $currentDay,
                                        "heure_debut" => $newSeanceStart,
                                        "heure_fin" => $pauseDebut,
                                        "duree" => $duree, 
                                        "duree_raw"=>$dureeraw,
                                        "module_id" => $classeModuleRandom->id,
                                        "classe_id" => $classe->id,
                                        "annee_id" => $annee_scolaire_id,
                                        "user_id" => $seanceManager->id,
                                        "timetable_id" => $timetable->id,
                                        "type_seance_id" => $randomTypeseances->id
                                    ]);
                                }




                                if (!$isIncluded) {
                                    dump('condition 2 pause non inclut');
                                    $deltaThours = $newSeanceEnd->diffInHours($pauseDebut, absolute: true);
                                    dump($deltaThours);

                                    $afterBreakSeanceEnd = $pauseEnd->copy();
                                    if ($deltaThours != 0) {

                                        /*modif suspecte */
                                        $afterBreakSeanceEnd = $afterBreakSeanceEnd->addHours($deltaThours);
                                        dump($afterBreakSeanceEnd->toTimeString());

                                        if ($count  != count($pauses)) {
                                            /*au prochain tour  de la boucle des pauses la fin de la seance sera
                                                         egale a la portion de la seance courente en intersection avec
                                                         la pause courante  */
                                            $newSeanceEnd = $afterBreakSeanceEnd->copy();
                                            /*au prochain tour  de la boucle des pauses le debut de la seance sera
                                                         egale a la fin de la pause courente(devenu pause predente au prochain tour) */
                                            $afterBreakSeanceEnd = $pauseEnd->copy();
                                        }
                                    }
                                } else {

                                    /*modif suspecte*/
                                    if ($count  != count($pauses)) {
                                        $afterBreakSeanceEnd = $pauseEnd->copy();
                                    }
                                    if ($count  == count($pauses)) {
                                        /* au dernier tour de la boucle des pauses $afterBreakSeanceEnd sera utilisé pour determiner la fin de la seance courante  */
                                        $afterBreakSeanceEnd = $newSeanceEnd->copy();
                                    }
                                    //   $afterBreakSeanceEnd = $pauseEnd->copy();
                                }


                                /*indique le debut de la seance pour le prochain tour dans la boucle des pauses */
                                $newSeanceStart = $afterBreakSeanceEnd->copy();
                            }



                            if ($count == count($pauses)) {

                                if ($intersectPause) {
                                    dump("last seance apres la pause" . " debut: " . $lastIntersectingPauseEnd->toTimeString() . " fin: " . $newSeanceEnd->toTimeString());
                                    if ($lastIntersectingPauseEnd->diffInHours($newSeanceEnd, absolute: true) != 0 && $lastIntersectingPauseStart->diffInHours($newSeanceEnd, absolute: true) != 0) {
                                        /*
                                    si  on est  la fin de la boucle des pauses (a  la derniere pause ) creer 
                                    la seance partant de la fin de la derniere pause avec laquelle la  seance courente 
                                    est entré en intersection et  finissant à  la fin  definis par $newseanceEnd
                                        */
                                        // $duree = $newSeanceEnd->diffInHours($lastIntersectingPauseEnd, absolute: true);
                                        $duree = seanceDuration($newSeanceEnd, $lastIntersectingPauseEnd);
                                        $dureeraw = seanceDuration($newSeanceEnd, $lastIntersectingPauseEnd,false);
                                        $randomSeanceState = randomState($newSeanceEnd, $now);
                                        $seance = Seance::factory()->createAbsentStudent($classe, $annee_scolaire_id)->create([
                                            'salle_id' => $salle_id,
                                            'etat' => $randomSeanceState,
                                            "date" => $currentDay,
                                            "heure_debut" => $lastIntersectingPauseEnd,
                                            "heure_fin" => $newSeanceEnd,
                                            "duree" => $duree,   
                                            "duree_raw"=>$dureeraw,
                                            "module_id" => $classeModuleRandom->id,
                                            "classe_id" => $classe->id,
                                            "annee_id" => $annee_scolaire_id,
                                            "user_id" => $seanceManager->id,
                                            "timetable_id" => $timetable->id,
                                            "type_seance_id" => $randomTypeseances->id
                                        ]);
                                    }
                                    dump('', $afterBreakSeanceEnd->copy()->toTimeString());
                                    // dump($currentSeanceEnd->toTimeString());
                                    $currentSeanceEnd = $newSeanceEnd->copy();
                                }
                            }
                            $count++;
                            /* fin de la boucle  des pauses*/
                        }



                        if (!$intersectPause) {
                            dump("seance sans intersection" . "debut: " . $newSeanceStart->toTimeString() . " fin: " . $newSeanceEnd->toTimeString());
                            // $duree = $newSeanceEnd->diffInHours($newSeanceStart, absolute: true);
                            $duree = seanceDuration($newSeanceEnd, $newSeanceStart);
                            $dureeraw = seanceDuration($newSeanceEnd, $newSeanceStart,false);

                            $randomSeanceState = randomState($newSeanceEnd, $now);
                            $seance = Seance::factory()->createAbsentStudent($classe, $annee_scolaire_id)->create([
                                'salle_id' => $salle_id,
                                'etat' => $randomSeanceState,
                                "date" => $currentDay,
                                "heure_debut" => $newSeanceStart,
                                "heure_fin" => $newSeanceEnd,
                                "duree" => $duree,
                                "duree_raw"=>$dureeraw,
                                "module_id" => $classeModuleRandom->id,
                                "classe_id" => $classe->id,
                                "annee_id" => $annee_scolaire_id,
                                "user_id" => $seanceManager->id,
                                "timetable_id" => $timetable->id,
                                "type_seance_id" => $randomTypeseances->id
                            ]);
                        }
                        dump(" after split  seance:{$i}    seance_enddddd_attt  " . $newSeanceEnd->toString());
                        dump('pauses loop_end_____');



                        $prevSeanceEnd = $currentSeanceEnd->copy();



                        /* fin de la boucle des seances */






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











                    }



                    $timetableDayCount++;
                    // return;
                }

             
            }
            $timetable_offset = $timetable_offset->addWeek();
            $timetable_offset_timestamp = $timetable_offset->timestamp;
        }
    }
}



////  $timetable = Timetable::factory()->create([
////                 'classe_id' => $classe->id,
////                 'annee_id' => $annee_scolaire_id,
////                 'date_debut' => $date_debut,
////                 'date_fin' =>  $date_fin,
////             ]);
////         Create a Carbon instance for the desired date

////         dump($date_fin->toDateTimeString());
////         dump($dayName);
////         $nowWeek = $now->week();

////         return [
////             'annee_id' => $annee_scolaire_id,
////             'date_debut' => $date_debut,
////             'date_fin' =>  $date_fin,
////         ];
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