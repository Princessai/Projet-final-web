<?php

namespace App\Http\Controllers;

use Closure;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Seance;
use App\Enums\roleEnum;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\SeanceResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Database\Eloquent\Builder;


class UserController extends Controller
{

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email:filter',
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }

        $validated = $validator->validated();
        $user = User::where(['email' => $validated['email']])->first();

        if (!$user) {
            return apiError(errors: ["email" => "wrong email"], statusCode: 401);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return apiError(message: 'wrong password', errors: ["password" => "wrong password"], statusCode: 401);
        }

        $response = [

            'token' => $user->createToken("token",  ['*'])->plainTextToken,
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

        $teachers = Role::where('label', roleEnum::Enseignant->value)->first()->roleUsers;
        $response = UserResource::collection($teachers);
        return apiSuccess(data: $response);
    }
    public function getTeacherSeancesClasse(Request $request, $teacher_id, $classe_id, $timestamp)
    {

        $currentTime = ($timestamp === 0) ? 0 : Carbon::createFromTimestamp($timestamp)->addMinutes(15);

        $formatedDate = ($currentTime !== 0) ? $currentTime->toDateTimeString() : 0;

        $seances = Seance::where(["user_id" => $teacher_id, "classe_id" => $classe_id])->where('heure_fin', '>=', $currentTime)->get();
        $seances = SeanceResource::collection($seances);
        $response = ["timestampToDate" => $formatedDate, 'seances' => $seances];

        return apiSuccess(data: $response);
    }

    public function getParentsChildren(Request $request, $parent_id)
    {
        // try {

        //     $user = User::with('parentEtudiants.etudiantsClasses')->findOrFail($parent_id);
        // } catch (\Throwable $th) {
        //     return apiError(message: "no such user");
        // }
        $user = User::with('parentEtudiants.etudiantsClasses');
        $user = apiFindOrFail($user, $parent_id, "no such user");
        if ($user->role->label != roleEnum::Parent->value) {
            return apiError(message: "the user $parent_id is not parent");
        }

        $parent = $user;


        $children = $parent->parentEtudiants;

        $response = UserResource::collection($children);
        return apiSuccess(data: $response);
    }

    public function loggedUserInfos(Request $request)
    {
        $user = $request->user();
        $response = new UserResource($user);
        return apiSuccess(data: $response);
    }
}
