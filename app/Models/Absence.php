<?php

namespace App\Models;

use App\Models\User;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Absence extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'seance_id',
        'coordinateur_id',
        'etat',
        "annee_id",
        
    ];


    public function etudiant(): BelongsTo // on récupère l'étudiant absent 
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function seance(): BelongsTo // la séance de laquelle l'étudiant est absent 
    {
        return $this->belongsTo(Seance::class);
    }
}
