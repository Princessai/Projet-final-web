<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\Classe;
use App\Models\Module;
use App\Enums\roleEnum;
use Illuminate\Support\Str;
use App\Models\ClasseEtudiant;
use App\Models\EtudiantParent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * 
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'remember_token' => Str::random(10),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone_number' => fake()->phoneNumber(),

        ];
    }

    public function userRole(Role $role, $disableDefaultConfig = false): static
    {


        return $this->state(function (array $attributes) use ($role) {
            $roleLabel = $role->label;
            $filePicturePath = null;

            $directoryName =  $roleLabel . 's';

            $files = Storage::files('/public/users/' . $directoryName);
            $pictureName=null;
            if (!empty($files)) {


                $randomPicture = fake()->randomElement($files);

                // dump('randomPicture', $randomPicture);

                $pictureExtension = File::extension($randomPicture);

                $uuid = Str::uuid();
                $pictureName = $uuid . '.' . $pictureExtension;

                $filePicturePath = "/public/users/$directoryName/$pictureName";
                if ($roleLabel == roleEnum::Parent->value) {
                    // dump('parent', $filePicturePath);
                }

                Storage::copy($randomPicture, $filePicturePath);


            }



            return [
                'picture' => $pictureName,
                'role_id' => $role->id,

            ];
        })->afterMaking(function (User $user) use ($role, $disableDefaultConfig) {

            // $roleEtudiantId = $roles->where("label", "etudiant")->first()->id;
            // $roleParent =$roles->where("label", "parent")->first();
            // $roleParentId = $roleParent->id;

            if ($disableDefaultConfig) return;
            if ($role->label == roleEnum::Etudiant->value) {
                dump('studennnnnnt');
                $roleParent = Role::where("label", roleEnum::Parent->value)->first();
                $roleParentId = $roleParent->id;

                $randomCase = rand(1, 3);
                if ($randomCase == 1) {
                    $user->parent_id = null;
                } else if ($randomCase == 2 && User::where('role_id', $roleParentId)->exists()) {

                    $randomParent = User::where('role_id', $roleParentId)->inRandomOrder()->first();
                    $user->parent_id = $randomParent->id;
                    dump('parentx',$randomParent);
                } else {
                    $randomParent = User::factory()->userRole($roleParent, true)->create();
                    $user->parent_id = $randomParent->id;
                    dump('parentxx',$randomParent);
                }
              
            }
        });
    }


    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
