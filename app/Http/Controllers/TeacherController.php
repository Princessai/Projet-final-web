<?php

namespace App\Http\Controllers;

use App\Enums\roleEnum;
use App\Models\User;
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

        $teacherData  = Arr::except($validatedData, ['modules', 'classes']);

        $role_id = Role::where(['label' => roleEnum::Enseignant->value])->first()->id;

        $teacherData['role_id'] =  $role_id;

        $newTeacher = User::create($teacherData);


        if (isset($validatedData['classes'])) {
            $classesIds = $validatedData['classes'];

            $newTeacher->enseignantClasses()->attach($classesIds);
        }

        if (isset($validatedData['modules'])) {
            $modulesIds = $validatedData['modules'];

            $newTeacher->enseignantModules()->attach($modulesIds);
        }



        return apiSuccess(data: $newTeacher, message: 'teacher created successfully !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {

        $validatedData = $request->validated();

        $teacher = new User;

        $teacher = apiFindOrFail($teacher, $id, 'no such teacher');

        $teacherData = Arr::except($validatedData, ['role_id', 'modules', 'classes']);


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
