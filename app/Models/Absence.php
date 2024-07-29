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

    public function etudiant(): BelongsTo // on récupère l'étudiant absent 
    {
        return $this->belongsTo(User::class);
    }
    public function seance(): BelongsTo // la séance de laquelle l'étudiant est absent 
    {
        return $this->belongsTo(Seance::class);
    }
}
