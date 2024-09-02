<?php

namespace App\Http\Controllers;

use App\Enums\roleEnum;
use App\Models\Classe;
use App\Models\Seance;
use Illuminate\Http\Request;
use App\Services\AnneeService;
use App\Services\ClasseService;
use App\Http\Resources\UserResource;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\UserCollection;
use Illuminate\Database\Eloquent\Builder;

class ClasseController extends Controller
{
    public function getAllClasses()
    {
        $response = ClasseResource::collection(Classe::with(['coordinateur.role', 'filiere', 'niveau'])->get());
        return apiSuccess($response);
    }

    public function getAllClasseStudents(Request $request, $classe_id)
    {
        $ClasseService = new ClasseService();
        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        // $classe  =  Classe::with(['etudiants'=>function($query) use($currentYearId){
        //     $query->wherePivot('classe_etudiants.annee_id', $currentYearId->id);
        // }]);
        // $classe = apiFindOrFail($classe, $classe_id, "no such class");

        $response = (new UserCollection($ClasseService->getClassCurrentStudent($classe_id, $currentYearId)))
            ->setCurrentYear($currentYearId)
            ->setRoleLabel(roleEnum::Etudiant->value);

        return apiSuccess(data: $response);
    }
    public function getClasseTeachers(Request $request, $classe_id)
    {

        // $seanceManager = $classeModuleRandom->enseignants()->whereHas('enseignantClasses', function ($query) use ($classe) {
        //     $query->where('classes.id', $classe->id);
        // })->first();
        // 'enseignantModules' =>
        // function ($query) use ($classe_id) {
        //     $query->whereHas('classes', function ($query) use ($classe_id) {
        //         $query->where('classes.id', $classe_id);
        //     });
        // }

        $classe = Classe::with([

            'enseignants' => function ($query) use ($classe_id) {
                $query->wherePivot('classe_id', $classe_id);
                $query->with('role');
                $query->with('enseignantModules', function ($query) use ($classe_id) {
                    $query->whereHas('classes', function ($query) use ($classe_id) {
                        $query->where('classes.id', $classe_id);
                    });
                });
            }


        ]);

        $classe = apiFindOrFail($classe, $classe_id, "no such class");
        // return $classe;

        $response = (new Usercollection($classe->enseignants))
            ->setRoleLabel(roleEnum::Enseignant->value);
        return apiSuccess(data: $response);
    }

    public function pogetStudentsAttendanceRecord($seance_id)
    {
        // .droppedStudents', 'classe.etudiants'

        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;

        $seance =   Seance::with([
            'module' => function ($query) use ($currentYearId) {


                $query->with('droppedStudents', function ($query) use ($currentYearId) {
                    $query->wherePivot('annee_id', $currentYearId);
                });
            },
            'classe' => function ($query) use ($currentYearId) {
                $query->CurrentYearStudents();
            
                // with('etudiants', function ($query) use ($currentYearId) {
                //     $query->wherePivot('annee_id', $currentYearId);
                // });
            }


        ]);

        $seance = apiFindOrFail($seance, $seance_id, 'no such session');
        $classe = $seance->classe;

        // return $classe;

       
        $ClasseService = new ClasseService();

        $response = (new UserCollection($ClasseService->getClassCurrentStudent($classe, $currentYearId)))
            ->setCurrentYear($currentYearId)
            ->setSeance($seance);

        return apiSuccess(data: $response);
    }
}
