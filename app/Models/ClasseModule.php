<?php

namespace App\Models;


use App\Models\CourseHour;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClasseModule extends Pivot
{

    protected $table = 'classe_module';
    protected $fillable = ['nbre_heure_total' ,
                'nbre_heure_effectue'];
    public function courseHours()
    {
        return $this->hasMany(CourseHour::class, 'classe_module_id'); 
    }
}
