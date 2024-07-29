<?php

namespace App\Models;

use App\Models\User;
use App\Models\Module;
use App\Models\Timetable;
use App\Models\ClasseEtudiant;
use App\Models\ClasseEnseignant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Classe extends Model
{
    use HasFactory;

    public function etudiants(): HasMany // les étudiants de la classe
    {
        return $this->hasMany(ClasseEtudiant::class);
    }
    // public function enseignant(): HasMany
    // {
    //     return $this->hasMany(ClasseEnseignant::class);
    // }

    public function enseignants(): BelongsToMany // les enseignants de la classe 
    {
        return $this->belongsToMany(User::class);
    }
    public function modules(): BelongsToMany // les modules de la classe 
    {
        return $this->belongsToMany(Module::class);
    }

    public function timetables(): HasMany // tous les emplois du temps de la classe
    {
        return $this->hasMany(Timetable::class);
    }
}
