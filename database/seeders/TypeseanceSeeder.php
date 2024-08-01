<?php

namespace Database\Seeders;

use App\Models\Typeseance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeseanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typeseances = require(base_path('data/typeseances.php'));
        foreach ($typeseances as $typeseance) {
          Typeseance::factory()->create(['label'=>$typeseance]);
        }
    }
}
