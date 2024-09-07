<?php

namespace App\Models;

use App\Models\User;
use App\Models\Classe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClasseEtudiant extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'niveau_id',
        'classe_id',
        'user_id',
        'annee_id'
        
    ];


    // public function etudiant(): BelongsTo // un étudiant de la classe 
    // {
    //     return $this->belongsTo(User::class);
    // }

    // public function classe(): BelongsTo // la classe d'un étudiant
    // {
    //     return $this->belongsTo(Classe::class);
    // }
}
