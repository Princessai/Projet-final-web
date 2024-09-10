<?php

namespace App\Models;


use App\Models\User;
use App\Models\CourseHour;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClasseModule extends Pivot
{

    protected $table = 'classe_module';
    protected $fillable = [
        'nbre_heure_total',
        'nbre_heure_effectue',
        'annee_id',
        'user_id',
        'statut_cours',
    ];
    public function courseHours()
    {
        return $this->hasMany(CourseHour::class, 'classe_module_id');
    }

    public function classeModuleTeacher()
    {
        return $this->belongsTo(User::class);
    }

    public function classeModule()
    {
        return $this->belongsTo(Module::class,'module_id');
    }
}

