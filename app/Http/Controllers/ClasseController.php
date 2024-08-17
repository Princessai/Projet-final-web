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
        $classe = Classe::findOrFail($classe_id);
        $response = UserResource::collection($classe->etudiants);
        return apiSuccess(data: $response);
    }
    public function getAllClasseTeachers(Request $request, $classe_id)
    {
        $classe = Classe::findOrFail($classe_id);
        $response = UserResource::collection($classe->enseignants);
        return apiSuccess(data: $response);
    }
}
