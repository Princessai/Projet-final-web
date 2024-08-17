<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annee extends Model
{
    use HasFactory;
    function etudiants() {
        return $this->belongsToMany(User::class,'classe_etudiants');
        
    }

}
