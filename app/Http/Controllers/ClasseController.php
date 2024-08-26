<?php

namespace App\Http\Controllers;

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
        $response = ClasseResource::collection(Classe::with(['coordinateur', 'filiere', 'niveau'])->get());
        return apiSuccess($response);
    }

    public function getAllClasseStudents(Request $request, $classe_id)
    {

        $classe  =  Classe::with('etudiants');
        $classe = apiFindOrFail($classe, $classe_id, "no such class");
        $AnneeService = new AnneeService();
        $ClasseService= new ClasseService();
        $currentYear = $AnneeService->getCurrentYear();
        $response = (new UserCollection($ClasseService->getClassCurrentStudent($classe,$currentYear)))->setCurrentYear($currentYear);

        return apiSuccess(data: $response);
    }
    public function getClasseTeachers(Request $request, $classe_id)
    {

        // $seanceManager = $classeModuleRandom->enseignants()->whereHas('enseignantClasses', function ($query) use ($classe) {
        //     $query->where('classes.id', $classe->id);
        // })->first();
        $classe = Classe::with([

            'enseignants' => [
                'enseignantModules' =>
                function ($query) use ($classe_id) {
                    $query->whereHas('classes', function ($query) use ($classe_id) {
                        $query->where('classes.id', $classe_id);
                    });
                }
            ]

        ]);

        $classe = apiFindOrFail($classe, $classe_id, "no such class");

        $response = UserResource::collection($classe->enseignants);
        return apiSuccess(data: $response);
    }

    public function getStudentsAttendanceRecord($seance_id)
    {

        $seance =   Seance::with(['module.droppedStudents', 'classe.etudiants']);
        $seance = apiFindOrFail($seance, $seance_id, 'no such session');
        $classe = $seance->classe;
        $AnneeService = new AnneeService();
        $ClasseService= new ClasseService();
        $currentYear= $AnneeService->getCurrentYear();
        $response = (new UserCollection($ClasseService->getClassCurrentStudent($classe,$currentYear)))
            ->setCurrentYear($currentYear)
            ->setSeance($seance);

        return apiSuccess(data: $response);
    }
}
