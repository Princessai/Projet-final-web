<?php

namespace App\Models;

use App\Models\User;
use App\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Droppe extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        "annee_id",
        "module_id",
        "classe_id",
        "isDropped",
        "created_at",
        "updated_at"
        

    ];
    public $timestamps = false;
    // public function module(): BelongsTo // les modules desquels les etudiants ont été droppés
    // {
    //     return $this->belongsTo(Module::class);
    // }
    // public function etudiant(): BelongsTo // 
    // {
    //     return $this->belongsTo(User::class);
    // }
}
