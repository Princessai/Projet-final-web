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

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class); // l'étudiant
    }
    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class); 
    }


}
