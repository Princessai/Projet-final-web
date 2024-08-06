<?php

namespace Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Annee>
 */
class AnneeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = CarbonImmutable::now();

        $currentYear = $now->year;

        $lastYear = $now->subYear()->year;

        dump($currentYear, $lastYear);

        return [
            'annee_scolaire' => "$lastYear - $currentYear",
        ];
    }
}
