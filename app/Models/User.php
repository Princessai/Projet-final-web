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

    
    public function etudiantClasse(): HasOne 
    {
        return $this->hasOne(ClasseEtudiant::class);
    }

    // public function classeEnseignant(): HasMany
    // {
    //     return $this->hasMany(ClasseEnseignant::class);
    // }

    public function enseignantClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class);
    }


    public function etudiantAbsences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function droppedUsers(): HasMany // les étudiants droppés
    {
        return $this->hasMany(Droppe::class);
    }

    public function enseignantModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class);
    }

    public function childInfo(): HasOne
    {
        return $this->hasOne(EtudiantParent::class, 'etudiant_id');
    }


    public function parentChildren(): HasMany
    {
        return $this->hasMany(EtudiantParent::class, 'parent_id');
    }

    public function etudiantPresences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

}
