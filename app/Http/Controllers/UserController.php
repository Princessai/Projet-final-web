<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Enums\roleEnum;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            "message" => "welcome $user->name $user->lastname",
            'token' => $user->createToken("token",  ['*'])->plainTextToken,
            // 'token' => $user->createToken("token",  ['*'], now()->addMinutes(15))->plainTextToken,

        ];
        return apiSuccess(data: $response, message: 'sucessfull connection');
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
    public function getParentsChildren(Request $request, $parent_id)
    {
        $parent = User::findOrFail($parent_id);

        $children = $parent->parentEtudiants;

        $response = UserResource::collection($children);
        return apiSuccess(data: $response);
    }
}
