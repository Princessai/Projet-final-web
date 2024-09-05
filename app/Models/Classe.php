<?php

namespace App\Models;

use App\Models\User;
use App\Models\Module;
use App\Models\Niveau;
use App\Models\Seance;
use App\Models\Timetable;
use App\Models\ClasseModule;
use App\Enums\seanceStateEnum;
use App\Models\ClasseEtudiant;
use App\Services\AnneeService;
use App\Services\ClasseService;
use App\Models\ClasseEnseignant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Classe extends Model
{
    use HasFactory;

    public $timestamps = false;
    public function etudiants()
    {
        return $this->belongsToMany(User::class, 'classe_etudiants')->withPivot('annee_id', 'niveau_id');
    }


    // public function classesEtudiants(): HasMany // les lignes correspondantes dans la table classe Etudiant (les associations entre les étudiants et les différentes classes)
    // {
    //     return $this->hasMany(ClasseEtudiant::class);
    // }
    // public function enseignant(): HasMany
    // {
    //     return $this->hasMany(ClasseEnseignant::class);
    // }

    public function enseignants(): BelongsToMany // les enseignants de la classe 
    {
        return $this->belongsToMany(User::class, 'classe_enseignant');
    }
    public function modules(): BelongsToMany // les modules de la classe 
    {
        // 'd_id' est la clé étrangère vers la table 'd' dans la table 'c'
        return $this->belongsToMany(Module::class)->withPivot(
            'id',
            'nbre_heure_total',
            'nbre_heure_effectue',
            'statut_cours',
            'annee_id'
        )
            ->using(ClasseModule::class);
    }

    public function timetables(): HasMany // tous les emplois du temps de la classe
    {
        return $this->hasMany(Timetable::class);
    }

    public function coordinateur(): BelongsTo
    {
        return $this->belongsTo(User::class); // user de type coordinateur
    }
    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class); // user de type coordinateur
    }
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class); // user de type coordinateur
    }
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    public function scopeCurrentYearStudents($query, $callback = null)
    {
        $ClasseService = new ClasseService;
        $ClasseService->loadClassCurrentStudent(query:$query, callback:$callback);
    }
}
