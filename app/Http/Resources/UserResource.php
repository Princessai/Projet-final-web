<?php

namespace App\Http\Resources;

use App\Enums\roleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array

    {
        $isStudent=$request->user()->role->label==roleEnum::Etudiant;

        return [

            "id" => $this->id,
            "name" => $this->name,
            "lastname" => $this->lastname,
            "picture" => $this->picture,
            "phone_number" => $this->phone_number,
            "email" => $this->email,
            "parent_id" => $this->whenNotNull($this->parent_id) ,
            "role_id" => $this->role_id,

        ];
    }
}
