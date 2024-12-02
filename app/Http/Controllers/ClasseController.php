<?php

namespace App\Http\Controllers;;

use App\Models\Role;
use App\Models\Classe;
use App\Models\Module;
use App\Models\Seance;
use App\Enums\roleEnum;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\crudActionEnum;

use App\Services\AnneeService;
use App\Services\ClasseService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ClasseRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ClasseResource;


use App\Http\Resources\UserCollection;
use function PHPUnit\Framework\isEmpty;
use Illuminate\Support\Facades\Validator;

class ClasseController extends Controller
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
    public function store(ClasseRequest $request)
    {

        // $validateModulesids = collect([]);
        // $noDuplicateTeacherModule = function (string $attribute, mixed $value, $fail) use (&$validateModulesids) {
        //     // apiSuccess($value)->send();
        //     $modules = $value["modules"];

        //     foreach ($modules as $key => $moduleId) {

        //         if ($validateModulesids->contains($moduleId)) {
        //             $fail("The {$attribute}.modules.$key is duplicated.");
        //         } else {
        //             $validateModulesids->push($moduleId);
        //         };
        //     }
        // };


        $validator = Validator::make($request->all(), [

            'teachers.*.modules.*' => ['integer'],
            'modules.*' => ['integer'],
        ]);



        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $validatedData = $validator->validated() + $request->validated();

        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;

        $classeData = Arr::except($validatedData, ['teachers', 'modules']);


        $classeModulesArr = [];
        $classeTeacherArr = [];
        if ($request->filled('teachers')) {


            $TeachersModulesArr = collect([]);
            $teachers = $request->teachers;
            foreach ($teachers as $teacherKey => $teacher) {

                $teacherModules = $teacher['modules'];
                $teacherId = $teacher['id'];
                $classeTeacherArr[] = $teacherId;



                foreach ($teacherModules as $moduleKey => $teacherModuleId) {

                    if ($TeachersModulesArr->contains($teacherModuleId)) {

                        $errors = ["teachers" => "teachers.$teacherKey.modules.$moduleKey is duplicated."];
                        return apiError(errors: $errors);
                    } else {
                        $TeachersModulesArr->push($teacherModuleId);
                    };

                    $classeModulesArr[$teacherModuleId] = ['annee_id' => $currentYearId, 'user_id' => $teacherId];
                }
            }
        }


        if ($request->filled('modules')) {
            $classeModules = $request->modules;
            foreach ($classeModules as $classeModuleId) {
                if (!isset($classeModulesArr[$classeModuleId])) {
                    $classeModulesArr[$classeModuleId] = ['annee_id' => $currentYearId, 'user_id' => null];
                }
            }
        }



        $newClasse = Classe::create($classeData);

        if (!empty($classeTeacherArr)) {
            $newClasse->enseignants()->attach($classeTeacherArr);
        }

        if (!empty($classeModulesArr)) {

            $newClasse->modules()->attach($classeModulesArr);
        }

        return apiSuccess(data: $newClasse, message: 'class created successfully !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;
        
        $classeQuery = Classe::with(['enseignants' => function ($query) use ($currentYearId, $id) {
            $query->select('users.id');


            $query->with('enseignantClasseModules', function ($query) use ($currentYearId, $id) {

                $query->where('annee_id', $currentYearId);
                $query->where('classe_id', $id);
                $query->with('classeModule');
                // $query->select("module_id","id","annee_id","classe_id");
            });
        }])->with('modules');

        $classe = apiFindOrFail($classeQuery, $id, 'no such classe');

        $response = ['classe' => new ClasseResource($classe)];
        
        return apiSuccess(data: $response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClasseRequest $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'modules.*' => ['array'],
            'modules.*.id' => ['integer'],
            'modules.*.action' => ['required', Rule::enum(crudActionEnum::class)],
            'teachers.*.action' => ['required', Rule::enum(crudActionEnum::class)],
            'teachers.*.modules.*' => ['array'],
            'teachers.*.modules.*.id' => ['integer'],
            'teachers.*.modules.*.action' => [Rule::enum(crudActionEnum::class)],
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $validatedData = $validator->validated() + $request->validated();

        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;

        $classeQuery = Classe::with(['modules' => function ($query) use ($currentYearId) {
            $query->with('enseignants');
            $query->wherePivot('annee_id', $currentYearId);
        }]);

        $classe = apiFindOrFail($classeQuery, $id, 'no such class');


        $classeModules = $classe->modules;

        $classeTeachers = $classe->enseignants;



        $classeData = Arr::except($validatedData, ['teachers', 'modules']);



        $detachTeachers = [];
        $attachTeachers = [];
        $detachTeacherModules = [];
        $attachTeacherModules = [];
        $teacherModulesInRequest = [];
        $newAffectedTeacherModule = [];

        if ($request->filled('teachers')) {
            $teachers = $request->teachers;

            foreach ($teachers as $teacherkey => $teacher) {

                $teacherId = $teacher['id'];
                $teacherModules = $teacher['modules'];
                $teacherAction = $teacher['action'];

                if ($teacherAction  == crudActionEnum::Delete->value) {
                    $teacherModules = [];
                    $detachTeachers[] = $teacherId;


                    foreach ($classeModules as $module) {
                        if ($module->pivot->user_id == $teacherId) {
                            $detachTeacherModules[] = $module->id;
                        }
                    }
                }



                // if ($teacherAction  == crudActionEnum::Update->value) {


                //     if (!empty($teacher['modules'])) {

                //         foreach ($teacherModules as $teacherModuleKey => $module) {

                //             $moduleAction = $module['action'];
                //             $moduleId = $module['id'];


                //             if ($moduleAction == crudActionEnum::Delete->value) {

                //                 $detachTeacherModules[] = $moduleId;
                //             }

                //             if ($moduleAction == crudActionEnum::Create->value) {
                //                 $isModuleWithoutTeacher = false;

                //                 $isAlreadyClasseModule = $classeModules->contains(function ($module) use ($moduleId, $teacherId, &$isModuleWithoutTeacher) {
                //                     if ($module->id == $moduleId) {
                //                         if ($module->pivot->user_id !== null) return true;
                //                         $isModuleWithoutTeacher = true;
                //                     }
                //                     // $module->id == $moduleId && $module->pivot->user_id !== null;

                //                 });

                //                 $isTeacherModulesInRequest = in_array($moduleId, $teacherModulesInRequest);


                //                 if ($isAlreadyClasseModule || $isTeacherModulesInRequest) {
                //                     $errors = ["teachers" => "teachers.$teacherkey.modules.$teacherModuleKey is duplicated."];
                //                     return  apiError(errors: $errors);
                //                 }



                //                 if ($isModuleWithoutTeacher == true) {
                //                     $newAffectedTeacherModule[$moduleId] = $teacherId;
                //                 } else {
                //                     $attachTeacherModules[$moduleId] = [
                //                         'annee_id' => $currentYearId,
                //                         'user_id' => $teacherId
                //                     ];
                //                 }

                //                 $teacherModulesInRequest[] = $moduleId;
                //             }
                //         }
                //     }
                // }

                if ($teacherAction  == crudActionEnum::Create->value) {
                    $isClasseTeacher = $classeTeachers->contains(function ($teacher) use ($teacherId) {
                        return $teacher->id == $teacherId;
                    });
                    if ($isClasseTeacher) {
                        $errors = ["teachers" => "teachers.$teacherkey is duplicated."];
                        return  apiError(errors: $errors);
                    }
                    $attachTeachers[] = $teacherId;
                }

                if ($teacherAction  == crudActionEnum::Update->value) {
                    $isClasseTeacher = $classeTeachers->contains(function ($teacher) use ($teacherId) {
                        return $teacher->id == $teacherId;
                    });
                    if (!$isClasseTeacher) {
                        $attachTeachers[] = $teacherId;
                    }
                }


                // if (!empty($teacherModules)) {

                //     foreach ($teacherModules as $teacherModuleKey => $module) {

                //         $moduleId = $module['id'];




                //         $isModuleWithoutTeacher = false;

                //         $isAlreadyClasseModule = $classeModules->contains(function ($module) use ($moduleId, $teacherId, &$isModuleWithoutTeacher) {
                //             if ($module->id == $moduleId) {
                //                 if ($module->pivot->user_id !== null) return true;
                //                 $isModuleWithoutTeacher = true;
                //             }
                //         });

                //         $isTeacherModulesInRequest = in_array($moduleId, $teacherModulesInRequest);


                //         if ($isAlreadyClasseModule || $isTeacherModulesInRequest) {
                //             $errors = ["teachers" => "teachers.$teacherkey.modules.$teacherModuleKey is duplicated."];
                //             return  apiError(errors: $errors);
                //         }



                //         if ($isModuleWithoutTeacher == true) {
                //             $newAffectedTeacherModule[$moduleId] = $teacherId;
                //         } else {

                //             $attachTeacherModules[$moduleId] = [
                //                 'annee_id' => $currentYearId,
                //                 'user_id' => $teacherId
                //             ];
                //         }

                //         $teacherModulesInRequest[] = $moduleId;
                //     }
                // }




                if (!empty($teacherModules)) {

                    foreach ($teacherModules as $teacherModuleKey => $module) {

                        $moduleId = $module['id'];

                        $moduleAction = $module['action'];

                        if ($teacherAction  == crudActionEnum::Update->value && $moduleAction == crudActionEnum::Delete->value) {

                            $detachTeacherModules[] = $moduleId;
                        }

                        if ($teacherAction  == crudActionEnum::Create->value || $moduleAction == crudActionEnum::Create->value) {
                            $isModuleWithoutTeacher = false;

                            $isAlreadyClasseModule = $classeModules->contains(function ($module) use ($moduleId, &$isModuleWithoutTeacher) {
                                if ($module->id == $moduleId) {
                                    if ($module->pivot->user_id !== null) return true;
                                    $isModuleWithoutTeacher = true;
                                }
                            });

                            $isTeacherModulesInRequest = in_array($moduleId, $teacherModulesInRequest);


                            if ($isAlreadyClasseModule || $isTeacherModulesInRequest) {
                                $errors = ["teachers" => "teachers.$teacherkey.modules.$teacherModuleKey is duplicated."];
                                return  apiError(errors: $errors);
                            }



                            if ($isModuleWithoutTeacher == true) {
                                $newAffectedTeacherModule[$moduleId] = $teacherId;
                            } else {

                                $attachTeacherModules[$moduleId] = [
                                    'annee_id' => $currentYearId,
                                    'user_id' => $teacherId
                                ];
                            }

                            $teacherModulesInRequest[] = $moduleId;
                        }
                    }
                }
            }
        }

        $detachModules = [];
        $attachModules = [];



        if ($request->filled('modules')) {
            $modules = $request->modules;

            foreach ($modules as $moduleKey => $module) {
                $moduleAction = $module['action'];
                $moduleId = $module['id'];

                if ($moduleAction == crudActionEnum::Create->value) {

                    $isModuleWithoutTeacher = false;

                    $isAlreadyClasseModule = $classeModules->contains(function ($module) use ($moduleId, $teacherId, &$isModuleWithoutTeacher) {
                        if ($module->id == $moduleId) {
                            if ($module->pivot->user_id !== null) return true;
                            $isModuleWithoutTeacher = true;
                        }
                    });

                    $isTeacherModulesInRequest = in_array($moduleId, $teacherModulesInRequest);

                    if ($isTeacherModulesInRequest || $isAlreadyClasseModule) {
                        $errors = ["modules" => "modules.$moduleKey is duplicated."];
                        return apiError(errors: $errors);
                    }


                    if ($isModuleWithoutTeacher == false) {

                        $attachModules[$moduleId] =  [
                            'annee_id' => $currentYearId,
                            'user_id' => null
                        ];
                    }

                    $teacherModulesInRequest[] = $moduleId;
                }


                if ($moduleAction == crudActionEnum::Delete->value) {
                    $detachModules[] = $moduleId;
                }
            }
        }





        if (!empty($classeData)) {
            $classe->update($classeData);
        }

        if (!empty($attachModules)) {
            $classe->modules()->attach($attachModules);
        }
        if (!empty($detachModules)) {
            $classe->modules()->detach($detachModules);
        }

        if (!empty($detachTeachers)) {
            $classe->enseignants()->detach($detachTeachers);
        }





        DB::table('classe_module')
            ->where([
                'annee_id' => $currentYearId,
                'classe_id' => $classe->id,
            ])->whereIn('module_id', $detachTeacherModules)->update(['user_id' => null]);

        if (!empty($attachTeachers)) {
            $classe->enseignants()->attach($attachTeachers);
        }

        if (!empty($attachTeacherModules)) {
            $classe->modules()->attach($attachTeacherModules);
        }

        foreach ($newAffectedTeacherModule  as $moduleId => $teacherId) {
            DB::table('classe_module')
                ->where([
                    'annee_id' => $currentYearId,
                    'classe_id' => $classe->id,
                    'module_id' => $moduleId
                ])->update(['user_id' => $teacherId]);
        }

        return apiSuccess(data: $classe, message: "class updated successfully !");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Classe::destroy($id);

        DB::table('classe_enseignant')
            ->where([
                'classe_id' => $id,
            ])->delete();

        return apiSuccess(message: 'class deleted successfully !');
    }


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
            ->setRoleLabel(roleEnum::Etudiant);

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
                // $query->wherePivot('classe_id', $classe_id);
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
            ->setRoleLabel(roleEnum::Enseignant);
        return apiSuccess(data: $response);
    }

    public function getStudentsAttendanceRecord($seance_id)
    {


        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;

        $seance =   Seance::with([

            'classe'

            => function ($query) use ($currentYearId) {
                $query->select('id');
                $query->CurrentYearStudents(callback: function ($query) {
                    $query->select(
                        'users.id',
                        'name',
                        'lastname',
                        'picture',
                        'phone_number',
                        'email'
                    );
                });
            }


        ]);

        $seance = apiFindOrFail($seance, $seance_id, 'no such session', ['id', 'module_id', 'classe_id']);

        $classe = $seance->classe;

        $droppesStudent = DB::table('droppes')
            ->where([
                'isDropped' => true,
                'annee_id' => $currentYearId,
                'module_id' => $seance->module_id,
                'classe_id' => $seance->classe->id

            ])->get('user_id');

        $seanceModule = new Module(['id' => $seance->module_id]);
        $seanceModule->setRelation('droppedStudents', $droppesStudent);
        $seance->setRelation('module', $seanceModule);

        $response = (new UserCollection($classe->etudiants))
            ->setCurrentYear($currentYearId)
            ->setSeance($seance)
            ->setRoleLabel(roleEnum::Etudiant);

        return apiSuccess(data: $response);
    }
}
