<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use App\Models\Classe;
use App\Models\Droppe;
use App\Models\Module;
use App\Models\Seance;
use App\Models\Absence;
use App\Models\Presence;
use App\Models\ClasseEtudiant;
use App\Models\EtudiantParent;
use App\Models\ClasseEnseignant;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;



    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    
    public function etudiantClasse(): HasOne  // un etudiant n'a qu'une seule classe
    {
        return $this->hasOne(ClasseEtudiant::class);
    }

    // public function classeEnseignant(): HasMany
    // {
    //     return $this->hasMany(ClasseEnseignant::class);
    // }

    public function enseignantClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'classe_enseignant');
    }


    public function etudiantAbsences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function droppedStudentsModules(): BelongsToMany // les étudiants droppés
    {
        return $this->belongsToMany(Module::class, 'droppes');
    }

    public function enseignantModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'enseignant_module');
    }

    public function etudiantParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id',);
    }


    public function parentEtudiants(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function etudiantPresences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function role(): BelongsTo // le rolee de l'utilisateur
    {
        return $this->belongsTo(Role::class);
    }

    public function enseignantSeances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }


    public function coordinateurClasses(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

}
