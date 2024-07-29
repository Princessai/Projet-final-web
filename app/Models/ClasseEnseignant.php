<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClasseEnseignant extends Model
{
    use HasFactory;

    // public function enseignant(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }

    // public function classe(): BelongsTo
    // {
    //     return $this->belongsTo(Classe::class);
    // }

}
