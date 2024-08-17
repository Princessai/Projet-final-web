<?php

namespace App\Models;

use App\Models\User;
use App\Models\Salle;
use App\Models\Module;
use App\Models\Retard;
use App\Models\Absence;
use App\Models\Presence;
use App\Models\Timetable;
use App\Models\Typeseance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seance extends Model
{
    use HasFactory;
    public $timestamps = false;
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
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
        return $this->BelongsTo(User::class); // user de type enseignant
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
        return $this->belongsTo(Typeseance::class);
    }
    public function retardsSeance(): HasMany
    {
        return $this->hasMany(Retard::class);
    }

}
