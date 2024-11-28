<?php

namespace App\Models;

use App\Models\User;
use App\Models\Salle;
use App\Models\Classe;
use App\Models\Module;
use App\Models\Retard;
use App\Models\Absence;
use App\Models\Presence;
use App\Models\Timetable;
use App\Models\TypeSeance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seance extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        "etat",
        "date",
        "heure_debut",
        "heure_fin",
        "salle_id",
        "module_id",
        'duree',
        "duree_raw",
        "user_id",
        "type_seance_id",
        "classe_id",
        "annee_id",
        "timetable_id",
    ];
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function delays(): HasMany
    {
        return $this->hasMany(Retard::class);
    }


    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->BelongsTo(User::class, "user_id"); // user de type enseignant
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function typeSeance(): BelongsTo
    {
        return $this->belongsTo(TypeSeance::class,);
    }


    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
}
