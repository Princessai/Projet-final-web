<?php

namespace Database\Seeders;

use App\Models\Annee;
use App\Models\YearSegment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class YearSegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $segments = require(base_path('data/yearSegments.php'));
        $years = Annee::all();
        foreach ($years as $year) {
            foreach ($segments as $segment) {
                $segment['annee_id'] = $year->id;
                YearSegment::create($segment);
            }
        }
    }
}
