<?php

namespace App\Http\Controllers;

use App\Enums\roleEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
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
        $validatedData = $request->validated();

        $UserService = new UserService;

        $UserService = new UserService;

        $teacherData  = Arr::except($validatedData, ['modules', 'classes']);

        ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash] = $UserService
            ->generatePassword($teacherData, roleEnum::Etudiant);

        $teacherData['password'] =  $generatedPasswordHash;
        ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash] = $UserService
            ->generatePassword($teacherData, roleEnum::Etudiant);

        $teacherData['password'] =  $generatedPasswordHash;


        $newTeacher = $UserService->createUser($teacherData, roleEnum::Enseignant);
        $newTeacher = $UserService->createUser($teacherData, roleEnum::Enseignant);


        if (isset($validatedData['classes'])) {
            $classesIds = $validatedData['classes'];

            $newTeacher->enseignantClasses()->attach($classesIds);
        }

        if (isset($validatedData['modules'])) {
            $modulesIds = $validatedData['modules'];

            $newTeacher->enseignantModules()->attach($modulesIds);
        }



        return apiSuccess(data: ['newTeacher' => $newTeacher, 'generatedPassword' => $generatedPassword], message: 'teacher created successfully !');
        return apiSuccess(data: ['newTeacher' => $newTeacher, 'generatedPassword' => $generatedPassword], message: 'teacher created successfully !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $UserService = new UserService;

        $teacher = User::with(['enseignantClasses', 'enseignantModules']);

        $response = $UserService->showUser($teacher, $id);

        return apiSuccess(data: $response);
        $UserService = new UserService;

        $teacher = User::with(['enseignantClasses', 'enseignantModules']);

        $response = $UserService->showUser($teacher, $id);

        return apiSuccess(data: $response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {

        $validatedData = $request->validated();

        $teacher = new User;

        $UserService = new UserService;

        $UserService = new UserService;

        $teacher = apiFindOrFail($teacher, $id, 'no such teacher');

        $teacherData = Arr::except($validatedData, ['role_id', 'modules', 'classes']);

        if ($request->filled('picture')) {

            $UserService->updatePicture(roleEnum::Enseignant, $teacher, $teacherData);
        }

        if ($request->filled('picture')) {

            $UserService->updatePicture(roleEnum::Enseignant, $teacher, $teacherData);
        }


        if (isset($validatedData['classes'])) {

            $classesIds = $validatedData['classes'];

            $teacher->enseignantClasses()->sync($classesIds);
        }

        if (isset($validatedData['modules'])) {

            $modulesIds = $validatedData['modules'];


            $teacher->enseignantModules()->sync($modulesIds);
        }

        $teacher->update($teacherData);


        return apiSuccess(message: 'teacher updated successfully !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRequest $request, string $id)
    {

        $teacher =  User::destroy($id);

        return apiSuccess(data: $teacher, message: 'teacher removed successfully !');
    }
}
