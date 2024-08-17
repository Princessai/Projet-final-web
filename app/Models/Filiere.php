<?php

namespace App\Models;

use App\Models\Classe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Filiere extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function sectorClasses(): HasMany
    {
        return $this->hasMany(Classe::class); // user de type coordinateur
    }


}
