<?php

namespace App\Models;

use App\Models\Classe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Niveau extends Model
{
    use HasFactory;
    public $timestamps = false;
    function etudiants() {
        return $this->belongsToMany(User::class,'classe_etudiants');
        
    }

    public function levelClasses(): HasMany
    {
        return $this->hasMany(Classe::class); // user de type coordinateur
    }

}
