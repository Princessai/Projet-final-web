<?php

namespace Database\Factories;

use App\Models\Annee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timetable>
 */
class TimetableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array

    {

    //     $days = require(base_path('data/weekDays.php'));
    //     $annee_scolaire_id = Annee::latest()->first()->id;


    //     $now=CarbonImmutable::now();
    //     $startOfDay = $now->startOfDay();
    //     $dayName =  $startOfDay->dayName;
    //     $dayNameIndex = array_search($dayName, $days);
    //     if ($dayNameIndex > 4) {
    //         $dayOffset = 7 - $dayNameIndex;
    //         $date_debut =  $startOfDay->addDays($dayOffset);
    //     } else {
    //         $date_debut =  $startOfDay->subDays($dayNameIndex);
    //     }

    //      $date_fin = $date_debut->addDays(4)->addHours(23);
         
    //     dump($date_debut->toDateTimeString());
    //     dump($date_fin->toDateTimeString());
    //     dump($dayName);
    //     // $nowWeek = $now->week();

         return [
    //         'annee_id' => $annee_scolaire_id,
    //         'date_debut' => $date_debut,
    //         'date_fin' =>  $date_fin,
        ];
     }
}
