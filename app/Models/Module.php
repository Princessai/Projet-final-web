<?php

namespace App\Models;

use App\Models\User;
use App\Models\Classe;
use App\Models\Droppe;
use App\Models\Seance;
use App\Models\ClasseModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'label',
    ];
    public $timestamps = false;
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class)->withPivot('id','nbre_heure_total',
        'nbre_heure_effectue','statut_cours','annee_id','user_id')->using(ClasseModule::class);
    }

    public function droppedStudents(): BelongsToMany // les étudiants droppés d'un module
    {
        return $this->belongsToMany(User::class, 'droppes');
    }

    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enseignant_module');
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    public function classesModules(): HasMany
    {
        return $this->hasMany(ClasseModule::class);
    }


}
