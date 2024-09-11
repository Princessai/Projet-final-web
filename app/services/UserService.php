<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Enums\roleEnum;
use Illuminate\Support\Arr;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;



class UserService
{

    public function createUser($data, roleEnum $roleEnum)
    {




        $role_id = Role::where(['label' => $roleEnum->value])->first()->id;


        $data['role_id'] =  $role_id;

        return  $newUser = User::create($data);
    }

    public function generatePassword($data, roleEnum $roleEnum)
    {

        $currentYear = app(AnneeService::class)->getCurrentYear();
        $roleLabelPrefix = [
            roleEnum::Admin->value => 'adm',
            roleEnum::Coordinateur->value => 'cdr',
            roleEnum::Parent->value => 'prt',
            roleEnum::Etudiant->value => 'etd',
            roleEnum::Enseignant->value => 'egt',
        ];

        $prefix =  $roleLabelPrefix[$roleEnum->value];

        $parentName = strtoupper($data['name'][0]);
        $parentLastName = strtoupper($data['lastname'][0]);
        $year = Carbon::parse($currentYear->date_debut)->year;

        $generatedPassword = $prefix . $parentName . $parentLastName . '@' . $year;

        $generatedPasswordHash = Hash::make($generatedPassword);

        return ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash];
    }

    public function updatePicture(roleEnum $roleEnum, User $user, string $input)
    {


        $request = request();

        $picture = $request->file($input);



        $pictureExtension = $picture->getClientOriginalExtension();
        $uuid = Str::uuid();
        $pictureName = $uuid . '.' . $pictureExtension;
        ["dirPath" => $dirPath] = $this->UserDirPictureConfig($roleEnum);
        // $dirName = $roleEnum->value . 's';
        // $dirPath= storage_path("app/public/users/$dirName");
        $picture->move($dirPath, $pictureName);


        $oldPicture = $user->picture;

        if ($oldPicture != null) {
            Storage::delete("$dirPath/$oldPicture");
        }
        $filePath = "$dirPath/$pictureName";

        return ["fileName" => $pictureName, "filePath" => $filePath];
    }

    public function UserDirPictureConfig(roleEnum $roleEnum)
    {
        $dirName = $roleEnum->value . 's';
        $dirPath = storage_path("app/public/users/$dirName");
        return ["dirName" => $dirName, "dirPath" => $dirPath];
    }

    public function showUser($userQuery, $user_id)
    {

        $user = apiFindOrFail($userQuery, $user_id, 'no such user');

        return $response = new  UserResource($user);
    }
}
