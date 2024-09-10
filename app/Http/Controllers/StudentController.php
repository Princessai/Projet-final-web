<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Classe;
use App\Enums\roleEnum;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\crudActionEnum;
use App\Services\UserService;
use App\Services\AnneeService;
use Illuminate\Validation\Rule;
use App\Services\StudentService;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(UserRequest $request)
    {




        $currentYear = app(AnneeService::class)->getCurrentYear();
        

        $baseRules = $request->rules($request);

        $specificUserRules = [
            'classe_id' => ["required", 'integer'],
            'parent' => ["required", 'array'],

        ];

        foreach ($baseRules as $field => $baseRule) {
            $specificUserRules["parent.$field"] = $baseRule;
        }

        $specificUserRules['parent.email'] = ['required', 'email'];


        $validator = Validator::make($request->all(),  $specificUserRules);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $validatedData = $request->validated();
        $validatedData += $validator->validated();

        $UserService = new UserService;

        $studentData  = Arr::except($validatedData, ['parent', 'classe_id']);

   

        $classeId = $validatedData['classe_id'];

        $classe = new Classe();

        $classe = apiFindOrFail($classe, $classeId, 'no such class');

        $parentId = null;
        if (isset($validatedData['parent'])) {

            $parentData = $validatedData['parent'];
            $parentExist = User::where(['email' => $parentData['email']])->first();

            if ($parentExist === null) {
                ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash] = $UserService->generatePassword($parentData, roleEnum::Parent);

                $parentData['password'] =  $generatedPasswordHash;

                $newParent = $UserService->createUser($parentData, roleEnum::Parent);
                $parentId = $newParent->id;
                $newParent->generatedPassword = $generatedPassword;
            } else {
                $newParent =$parentExist;
                $parentId = $parentExist->id;
            }
        }

        $studentData['parent_id'] = $parentId;

        ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash] = $UserService->generatePassword($studentData, roleEnum::Etudiant);

        $studentData['password'] =  $generatedPasswordHash;

        $newStudent = $UserService->createUser($studentData, roleEnum::Etudiant);


        $niveauId = $classe->niveau_id;

        $newStudent->etudiantsClasses()->attach($classeId, ['annee_id' => $currentYear->id, 'niveau_id' => $niveauId]);

        $newStudent->setRelation('etudiantsClasses', $classe);


        $newStudent->setRelation('etudiantParent', $newParent);

        $newStudent->generatedPassword = $generatedPassword;


        return apiSuccess(data: $newStudent, message: 'new student added successfully !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $UserService = new UserService;

        $student = User::with(['etudiantsClasses']);

        $response = $UserService->showUser($student, $id);

        return apiSuccess(data: $response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {


        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        $baseRules = $request->rules($request);

        $specificUserRules = [
            'classe_id' => ["required", 'integer'],
            'classe_action' => [Rule::requiredIf(fn() =>  $request->filled('classe_id')), Rule::enum(crudActionEnum::class)],
            'parent' => ['array'],

        ];

        foreach ($baseRules as $field => $baseRule) {
            $specificUserRules["parent.$field"] = $baseRule;
        }

        $specificUserRules['parent.email'] = ['required', 'email'];


        $validator = Validator::make($request->all(),  $specificUserRules);


        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $validatedData = $request->validated();
        $validatedData += $validator->validated();


        $UserService = new UserService;

        $student = apiFindOrFail(new User, $id, 'no such student');

        $studentData = Arr::except($validatedData, ['classe_id', 'parent', 'role_id', 'classe_action']);


      

        if (isset($validatedData['classe_id'])) {

            $classeId = $validatedData['classe_id'];

            $classe = apiFindOrFail(new Classe(), $classeId, 'no such class');

            $niveauId = $classe->niveau_id;

            if ($validatedData['classe_action'] == crudActionEnum::Create->value) {
                $student->etudiantsClasses()
                    ->attach($classeId, ['annee_id' => $currentYearId, 'niveau_id' => $niveauId]);
            }

            if ($validatedData['classe_action'] == crudActionEnum::Delete->value) {

                $student->etudiantsClasses()
                    ->wherePivot('annee_id', $currentYearId)
                    ->wherePivot('classe_id', $classeId)
                    ->detach($classeId);
            }
        }

        if (isset($validatedData['parent'])) {
            $parentId =null;
            $parentData = $validatedData['parent'];
            $parent=$student->etudiantParent;
            if($parent===null){
               
                ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash] = $UserService->generatePassword($parentData, roleEnum::Parent);

                $parentData['password'] =  $generatedPasswordHash;

                $newParent = $UserService->createUser($parentData, roleEnum::Parent);
                $parentId = $newParent->id;
                $newParent->generatedPassword = $generatedPassword;
                $student->setRelation('etudiantParent', $newParent);
            }
            $studentData['parent_id'] = $parentId;

            
           
        }

       


        $student->update($studentData);


        return apiSuccess(message: 'student updated successfully !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
      

        User::destroy($id);

        return apiSuccess(message: 'student removed successfully !');
    }
}
