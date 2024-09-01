<?php

namespace App\Models;

use App\Models\User;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Retard extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'seance_id',
        "annee_id",
        "duree",
        "duree_raw",
        "module_id",
        'seance_heure_debut',
        'seance_heure_fin',

    ];



    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class); // l'étudiant
    }
    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }
}
