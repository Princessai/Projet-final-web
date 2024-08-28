<?php

namespace App\Models;

use App\Models\Classe;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'classe_id',
        "date_debut",
        "date_fin",
        "commentaire",
        "classe_id",
        'annee_id'
    ];


    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }
}
