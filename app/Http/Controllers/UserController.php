<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Seance;
use App\Enums\roleEnum;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\AnneeService;
use App\Services\StudentService;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

use App\Http\Resources\UserCollection;
use function PHPUnit\Framework\isNull;
use Illuminate\Database\Query\Builder;
use App\Http\Resources\SeanceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function forceDeleteUser($user_id, $roleLabel)
    {

        if ($roleLabel == roleEnum::Etudiant->value) {
            $studentQuery = User::with(['etudiantParent' => function ($query) {
                $query->select('id');
                $query->withCount('parentEtudiants');
            }]);

            $user = apiFindOrFail($studentQuery, $user_id, 'no such student');
        }



        User::where('id', $user_id)->forceDelete();

        if ($roleLabel == roleEnum::Etudiant->value && $user->etudiantParent != null) {

            $parentChildrenCount = $user->etudiantParent->parent_etudiants_count;
            if ($parentChildrenCount == 1) {

                User::where('id', $user->parent_id)->forceDelete();
            }
        }


        return apiSuccess(message: "deleted successfully !");
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email:filter',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $currentYear = app(AnneeService::class)->getCurrentYear();

        $validated = $validator->validated();
        $user = User::with('role')->where(['email' => $validated['email']])->first();

        if (!$user) {
            return apiError(errors: ["email" => "wrong email"], statusCode: 401);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return apiError(message: 'wrong password', errors: ["password" => "wrong password"], statusCode: 401);
        }

        $userRole = $user->role;
        $response = [

            'user' => new UserResource($user, roleLabel: $userRole->label),
            'token' => $user->createToken("token",  ['*'])->plainTextToken,
            'currentYear' => $currentYear

            // 'token' => $user->createToken("token",  ['*'], now()->addMinutes(15))->plainTextToken,

        ];
        return apiSuccess(data: $response, message: "welcome $user->name $user->lastname");
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return apiSuccess(data: $user, message: 'logout successfull');
    }

    public function getAllTeachers(Request $request)
    {

        $role = Role::with(['roleUsers' => function ($query) {

            $query->with('enseignantModules');
        }])
            ->where('label', roleEnum::Enseignant->value)->first();


        // $teachers = Role::with('roleUsers.enseignantModules')->where('label', )->first()->roleUsers;


        $response = (new UserCollection($role->roleUsers))
            ->setRoleLabel(roleEnum::Enseignant);
        return apiSuccess(data: $response);
    }
    public function getUserSeances(Request $request, $user_id, $timestamp = null)
    {
        $currentYear = app(AnneeService::class)->getCurrentYear();
        $currentYearId = $currentYear->id;

        $currentTime = now();
        $timestamp = ($timestamp === null) ? 0 : Carbon::createFromTimestamp($timestamp);


        $user = User::with('role');
        $user = apiFindOrFail(query: $user, id: $user_id, message: 'no such  user');

        $userRole = $user->role;

        if ($userRole->label == roleEnum::Etudiant->value) {
            $studentService = new StudentService;
            $user->loadMissing(['etudiantsClasses' => function ($query) use ($studentService, $currentYearId, $timestamp) {
                $query->orderByPivot('id', 'desc')->take(1);
                $query->with('seances', function ($query) use ($currentYearId, $timestamp) {

                    if ($timestamp !== null) {
                        $query->where('heure_fin', '>=', $timestamp);
                    } else {
                        $query->where('annee_id', $currentYearId);
                    }

                    $query->with(['typeSeance', 'module', 'salle'])
                        ->orderBy('id', 'desc');
                });
            }]);


            $studentClasse = $user->etudiantsClasses->first();
            $seances = $studentClasse->seances;
        } else {
            $eagerLoadedRelation = ['typeSeance', 'classe.filiere', 'classe.niveau', 'module', 'salle'];
            if ($timestamp !== null) {
                $seances = Seance::with($eagerLoadedRelation)->where(['user_id' => $user->id])->where('heure_fin', '>=', $timestamp)->orderBy('id', 'desc')->get();
            } else {
                $seances = Seance::with($eagerLoadedRelation)->where(['user_id' => $user->id, 'annee_id' => $currentYear->id])->orderBy('id', 'desc')->get();
            };
        }



        // ->transform(function($seance){
        //     return [
        //         "id" => $seance->id ,
        //         "etat" =>  $seance->etat,
        //         "date" => $seance->date ,
        //         "attendance" => $seance->attendance ,
        //         "heure_debut" =>  $seance->heure_debut,
        //         "heure_fin" =>  $seance->heure_fin,
        //         "duree" =>  $seance->duree,
        //         "salle" =>  $seance->salle,
        //         "module" => new ModuleResource($seance->module) ,

        //         "timetable_id" =>  $seance->timetable_id,
        //         "type_seance" => $seance->typeSeance,
        //         "classe" => new ClasseResource($seance->classe)  ,
        //         "annee_id" => $seance->annee_id 

        //     ];
        // })
        // ->groupBy(function ( $item, int $key) use($currentTime){

        //     if($item['heure_fin'] >= $currentTime) {
        //         return 'coming';
        //     }

        //     return 'passed';
        // });

        $response =  new SeanceCollection($seances);
        $response->setCurrentTime($currentTime);


        return apiSuccess(data: $response);
    }

    public function getParentsChildren(Request $request, $parent_id)
    {

        $children = User::with(['etudiantsClasses' => function ($query) {
            // $query->with('etudiantsClasses', function ($query) {
            $query->orderByPivot('id', 'desc')->take(1);
            // });
        }])->where(['parent_id' => $parent_id])->get();


        // $user = apiFindOrFail($user, $parent_id, "no such user");


        // if ($user->role->label != roleEnum::Parent->value) {

        //     return apiError(message: "the user $parent_id is not parent");
        // }

        // $parent = $user;

        // $children = $parent->parentEtudiants;

        $response = (new Usercollection($children))
            ->setRoleLabel(roleEnum::Etudiant);
        return apiSuccess(data: $response);
    }

    public function loggedUserInfos(Request $request)
    {
        $currentYear = app(AnneeService::class)->getCurrentYear();
        $user = $request->user()
            ->load('role');
        $userRole = $user->role;
        if ($userRole->label == roleEnum::Etudiant->value) {
            $user->load(['etudiantsClasses' => function ($query) {
                $query->orderByPivot('id', 'desc')->take(1);
                $query->with(['niveau', 'filiere']);
            }]);
            $user->setRelation('etudiantsClasses',   $user->etudiantsClasses->first());
        }

        $response = [
            'currentYear' => $currentYear,
            'user' => new UserResource($user)
        ];

        return apiSuccess(data: $response);
    }

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
    public function store(Request $request)
    {

        $roles = Role::all();
        if (!$request->has('role_id')) {
            return apiError(message: "role_id is required");
        }


        $userRole = $roles->where('id', $request->role_id)->first();
        if (is_null($userRole)) {
            return apiError(message: "no such role in the database");
        }



        $baseRules = [
            'name' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::min(8)],
            'phone_number' => 'required',
            'picture' => 'nullable',
        ];

        $parentRules = ['parent' => 'nullable|array'];

        foreach ($baseRules as $field => $baseRule) {
            $parentRules["parent.$field"] = $baseRule;
        }

        $rules = $baseRules + $parentRules;

        $specificRules = [
            'role_id' => 'required|integer',

        ];
        if ($userRole->label != roleEnum::Admin->value && $userRole->label != roleEnum::Parent->value) {
            $specificRules['classe_id'] = ["array"];
            $specificRules['classe_id.*'] = ["integer"];
        }



        $validator = Validator::make($request->all(), $rules + $specificRules);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $query = User::with('role');
        $user = apiFindOrFail($query, $id);
        if($user->role->label==roleEnum::Etudiant->value){
            $user->load(['etudiantsClasses' => function ($query) {

                $query->orderByPivot('id', 'desc')->take(1);
            }]);
        }

        return apiSuccess( new UserResource($user));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }


    public function updateUserPicture(Request $request, $user_id)
    {

        $validator = Validator::make($request->all(),  [
            'picture' => ['required', 'nullable', 'file', 'mimes:jpg,jpeg', 'max:10240']
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }


        $UserService = new UserService;

        $userQuery = User::with('role');



        $user = apiFindOrFail($userQuery, $user_id, 'no such user');
        $role = $user->role;
        $request = request();
        $picture = $request->file('picture');
        $roleEnum = roleEnum::tryFrom($role->label);
        ["dirName" => $dirName] = $UserService->UserDirPictureConfig($roleEnum);

        ["fileName" => $pictureName] = $UserService->updatePicture($roleEnum, $user, 'picture');

        $user->update(['picture' => $pictureName]);
        $fileUrl = asset("storage/users/$dirName/$pictureName");

        return apiSuccess(data: $fileUrl, message: 'picture updated successfully !');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
