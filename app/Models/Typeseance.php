<?php

namespace App\Models;

use App\Models\Seance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Typeseance extends Model
{
    use HasFactory;
    public $timestamps = false;
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

}
