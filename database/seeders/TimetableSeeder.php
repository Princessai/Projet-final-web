<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Annee;
use App\Models\Classe;
use App\Models\Timetable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TimetableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = require(base_path('data/weekDays.php'));

        $classes = Classe::all();
        $annee_scolaire_id = Annee::latest()->first()->id;

        $now = CarbonImmutable::now();
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


        $timetable_offset =  $date_debut->subDay()->subMonths(8);
        $timetable_offset_timestamp =  $timetable_offset->timestamp;

        $pauses = [
            ["name" => "midi", "debut" => 12, "fin" => 14],
            ["name" => "recreation", "debut" => "10:45", "fin" => 11]
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


        while ($timetable_offset_timestamp <= $date_fin->timestamp) {
            // if($timetable_offset_timestamp==$date_fin->timestamp)break;
            $timetableStart = $timetable_offset;
            $timetableEnd = $timetable_offset->addDays(4)->endOfDay();
            $timetableDayCount = 0;
            $maxDays = 4;
            // $timetable = Timetable::factory()->create([
            //     // 'classe_id' => $classe->id,
            //     'annee_id' => $annee_scolaire_id,
            //     'date_debut' => $timetableStart,
            //     'date_fin' =>  $timetableEnd,
            // ]);
            while ($timetableDayCount < $maxDays) {
                // dump('test');
                $dayStart = 9;
                $dayEnd = 17;
                // $dayDuration=$dayEnd-$dayStart;
                $dayDuration = 6;
                $dayStep = 1;
                $dayAvailableHours = $dayDuration;
                $timetableDayStart = $timetableStart->addHours($dayStart);
                // $seanceCount=rand(1,3);
                $seanceCount = rand(1, 2);
                // $seanceRandomHours=[];
                $prevSeanceEnd = $timetableDayStart;
                for ($i = 1; $i <= $seanceCount; $i++) {


                    if ($seanceCount == 1) {
                        $seanceRandomHoursCount = $dayDuration;
                    }

                    if ($seanceCount != 1 && $i == $seanceCount) {
                        $seanceRandomHoursCount = $dayAvailableHours;
                    } else {
                        $seanceRandomHoursCount = rand($dayStep, $dayAvailableHours - (($seanceCount - $i) * $dayStep));
                        $dayAvailableHours -= $seanceRandomHoursCount;
                        // $seanceRandomHours[]=$seanceRandomHoursCount;

                    }

                    $currentSeanceStart = $prevSeanceEnd->copy();
                    $currentSeanceEnd = $currentSeanceStart->addHours($seanceRandomHoursCount);

                    $newSeanceStart = $currentSeanceStart->copy();
                    $newSeanceEnd = $currentSeanceEnd->copy();

                    $count = 1;

                    foreach ($pauses as $key => $pause) {
                        dump($pause['name']);

                        $pauseDebut = $pause['debut'];
                        $parsedTime = Carbon::createFromTimeString("$pauseDebut");
                        $pauseDebutHours = $parsedTime->hour;

                        dump('$pauseDebutHours', $pauseDebutHours);
                        $pauseDebutMinutes = $parsedTime->minute;


                        $pauseEnd = $pause['fin'];

                        // dump('$pauseEnd' ,$pauseEnd);
                        $parsedTime = Carbon::createFromTimeString("$pauseEnd");
                        $pauseEndHours = $parsedTime->hour;
                        // dump('$pauseEndHours',$pauseEndHours);
                        $pauseEndMinutes = $parsedTime->minute;

                        $pauseDebut = $timetableStart->addHours($pauseDebutHours)->addMinutes($pauseDebutMinutes);

                        $pauseEnd = $timetableStart->addHours($pauseEndHours)->addMinutes($pauseEndMinutes);

                        // dump( 'pause',$pauseDebut->toString(), 'end',$pauseEnd->toString());

                        if ($newSeanceStart->lessThan($pauseEnd) && $newSeanceEnd->greaterThan($pauseEnd)) {
                            /*creation d'une nouvelle seance  avec comme debut le debut initial et comme fin la fin de la pause*/
                            $newSeanceEnd = $pauseDebut->copy();
                            // $newSeanceStart

                            // $currentSeanceEnd=$pauseDebut->copy();

                            /* creation*** pas vraiment creer d"une seance ayant comme debut le debut de la pause et comme fin la fin initiale */
                            $newSeanceStart = $pauseEnd->copy();
                            /*fin initiale de la seance*/
                            $newSeanceEnd = $currentSeanceEnd->copy();
                        }
                        $count++;
                        if ($count == count($pauses)) {
                            $currentSeanceStart = $newSeanceStart->copy();
                            $currentSeanceEnd = $newSeanceEnd->copy();
                        }
                    }

                    // function intersectWithPause($interval1, $interval2, $pauses, $index = 0,)
                    // {
                    //     if (count($pauses) == $index) return;

                    //     if ($interval1['start']->lessThan($interval2['start']) && $interval1['end']->greaterThan($interval2['end'])) {
                    //         $interval1EndCopy = $interval1['end']->copy();
                    //         $interval1StartCopy = $interval1['start']->copy();
                    //         $interval1['end'] = $interval2['start'];
                    //         /* new seance $interval1StartCopy to $interval1['end']=$interval2['start']*/
                    //         /*seconde interval */
                    //         $interval1['start'] = $interval2['end'];
                    //     }
                    // }


                    $prevSeanceEnd = $currentSeanceEnd->copy();
                }
                // rsort($seanceRandomHours);
                // $prevSeanceStart=$timetableDayStart;
                // $prevSeanceEnd;
                // foreach ($seanceRandomHours as $seanceRandomHour) {



                //     $prevSeanceStart=$timetableDayStart;
                //     $prevSeanceEnd=$timetableDayStart->addHours($seanceRandomHour);
                // }


                $timetableDayCount++;
            }

            $timetable_offset = $timetable_offset->addWeek();
            $timetable_offset_timestamp = $timetable_offset->timestamp;
            dump('$timetable_offset_timestamp inloop', $timetable_offset->toString());
        }
        dump('$timetable_offset_timestamp outloop', $timetable_offset->toDateTimeString());


        foreach ($classes as $classe) {
            $timetable = Timetable::factory()->create([
                'classe_id' => $classe->id,
                'annee_id' => $annee_scolaire_id,
                'date_debut' => $date_debut,
                'date_fin' =>  $date_fin,
            ]);
        }



        // Create a Carbon instance for the desired date

        // dump($date_fin->toDateTimeString());
        // dump($dayName);
        // $nowWeek = $now->week();

        // return [
        //     'annee_id' => $annee_scolaire_id,
        //     'date_debut' => $date_debut,
        //     'date_fin' =>  $date_fin,
        // ];
    }
}
