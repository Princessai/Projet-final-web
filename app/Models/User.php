<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use App\Models\Annee;
use App\Models\Classe;
use App\Models\Droppe;
use App\Models\Module;
use App\Models\Retard;
use App\Models\Seance;
use App\Models\Absence;
use App\Models\Presence;
use App\Enums\seanceStateEnum;
use App\Models\ClasseEtudiant;
use App\Models\EtudiantParent;
use App\Models\ClasseEnseignant;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable
{
    use   HasApiTokens, HasFactory, Notifiable;



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

    public function etudiantsClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'classe_etudiants')->withPivot('annee_id', 'niveau_id');
    }

    function etudiantsNiveaux()
    {
        return $this->belongsToMany(Niveau::class, 'classe_etudiants');
    }
    // public function etudiantClasse(): HasOne  // un etudiant n'a qu'une seule classe
    // {
    //     return $this->hasOne(ClasseEtudiant::class);
    // }

    // public function classeEnseignant(): HasMany
    // {
    //     return $this->hasMany(ClasseEnseignant::class);
    // }

    public function enseignantClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'classe_enseignant');
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
    public function etudiantAbsences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }
    public function etudiantRetards(): HasMany
    {
        return $this->hasMany(Retard::class);
    }


    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder|Model $query, $module_id = null, $currentYear_id = null, $timestamp1 = null, $timestamp2 = null): void
    {

       
        $baseQuery =   $query->withSum(['etudiantAbsences as missedHoursSum' => function ($query) use ($currentYear_id, $module_id, $timestamp2, $timestamp1) {

            if ($module_id !== null) {
                $query->where('module_id', $module_id);
            }
            if ($timestamp1 === null && $timestamp2 === null) {
                $query->where('annee_id', $currentYear_id);
            }
            if ($timestamp1 !== null && $timestamp2 === null) {
                $query->where('seance_heure_debut', '>', $timestamp1);
            }

            if ($timestamp1 !== null && $timestamp2 !== null) {
                $query->whereBetween('seance_heure_debut', [$timestamp1, $timestamp2]);
            };
        }], 'duree');

        if ($timestamp1 === null && $timestamp2 === null) {


            $baseQuery->with(['etudiantsClasses' => function ($query) use ($module_id, $currentYear_id, $timestamp2, $timestamp1) {

                $query->wherePivot('annee_id', $currentYear_id);


                $query->withSum(['modules as workedHoursSum' => function ($query) use ($currentYear_id, $module_id) {
                    $query->where('annee_id', $currentYear_id);
                    if ($module_id !== null) {
                        $query->where('module_id', $module_id);
                    }
                }], 'classe_module.nbre_heure_effectue');
            }]);
        } else {
            if ($timestamp1 !== null && $timestamp2 === null) {
                $anneeIds = Annee::where('date_fin', '>', $timestamp1)->get('id')->pluck('id');
            }
            if ($timestamp1 !== null && $timestamp2 !== null) {
                $anneeIds = Annee::whereBetween('date_fin', [$timestamp1, $timestamp2])->get('id')->pluck('id');
            }

            $baseQuery->with(['etudiantsClasses' => function ($query) use ($module_id, $anneeIds,  $timestamp1, $timestamp2,) {

                $query->wherePivotIn('annee_id', $anneeIds);

                $query->withSum(['seances as workedHoursSum' => function ($query) use ($module_id, $timestamp1, $timestamp2) {

                    $query->whereColumn('classe_etudiants.annee_id', 'seances.annee_id');
                    $baseWhereClause = ['etat' => seanceStateEnum::Done->value];

                    if ($module_id !== null) {
                        $baseWhereClause['module_id'] = $module_id;
                    }

                    $query->where($baseWhereClause);




                    if ($timestamp1 !== null && $timestamp2 === null) {
                        $query->where('heure_debut', '>', $timestamp1);
                    }

                    if ($timestamp1 !== null && $timestamp2 !== null) {
                        $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                    };
                }], 'duree');
            }]);


                // $classe = $baseQuery->withSum(['seances as workedHoursSum' => function ($query) use ($module_id, $timestamp2, $timestamp1) {

                //     $baseWhereClause = ['etat' => seanceStateEnum::Done->value];



                //     if ($module_id !== null) {
                //         $baseWhereClause['module_id'] = $module_id;
                //     }


                //     $query->where($baseWhereClause);

                //     if ($timestamp1 !== null && $timestamp2 === null) {
                //         $query->where('heure_debut', '>', $timestamp1);
                //     }

                //     if ($timestamp1 !== null && $timestamp2 !== null) {
                //         $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                //     };
                // }], 'duree')
                // ->with('seances',function($query)use($module_id,$currentYear,$timestamp2,$timestamp1){

                //     $baseWhereClause=['etat'=> seanceStateEnum::Done->value];



                //     if($module_id!==null){
                //         $baseWhereClause['module_id']=$module_id;
                //     }


                //     $query->where($baseWhereClause);

                //     if ($timestamp1 !== null && $timestamp2 === null) {
                //         $query->where('heure_debut', '>', $timestamp1);

                //         }

                //         if ($timestamp1 !== null && $timestamp2 !== null) {
                //              $query->whereBetween('heure_debut', [$timestamp1, $timestamp2]);
                //         };

                // })

            ;
        }




    }
}
