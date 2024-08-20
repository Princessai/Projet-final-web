<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use Illuminate\Http\Request;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\UserResource;

class ClasseController extends Controller
{
    public function getAllClasses()
    {
        $response = ClasseResource::collection(Classe::with(['coordinateur', 'filiere', 'niveau'])->get());
        return apiSuccess($response);
    }

    public function getAllClasseStudents(Request $request, $classe_id)
    {
        $classe = new Classe;  
        $classe= apiFindOrFail($classe,$classe_id,"no such class");
        $response = UserResource::collection($classe->etudiants);
        return apiSuccess(data: $response);
    }
    public function getClasseTeachers(Request $request, $classe_id)
    {

        // $seanceManager = $classeModuleRandom->enseignants()->whereHas('enseignantClasses', function ($query) use ($classe) {
        //     $query->where('classes.id', $classe->id);
        // })->first();
        $classe = Classe::with([

            'enseignants'=> ['enseignantModules'=> 
                        function ( $query) use($classe_id) {
                            $query->whereHas('classes', function ($query) use ($classe_id) {
                                $query->where('classes.id', $classe_id);
                            });
                        } 
                    ]
        
        ]);

        $classe= apiFindOrFail($classe,$classe_id,"no such class");

        $response = UserResource::collection($classe->enseignants);
        return apiSuccess(data: $response);
    }

}
