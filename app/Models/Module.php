<?php

namespace App\Models;

use App\Models\User;
use App\Models\Classe;
use App\Models\Droppe;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;
    public $timestamps = false;
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class);
    }

    public function droppedStudents(): BelongsToMany // les étudiants droppés d'un module
    {
        return $this->belongsToMany(User::class, 'droppes');
    }

    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }


}
