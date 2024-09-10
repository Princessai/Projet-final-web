<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'coordinateur' => new UserResource($this->whenLoaded('coordinateur')),
            'filiere' => $this->whenLoaded('filiere'),
            'niveau' => $this->whenLoaded('niveau'),
            'teachers' => new UserCollection($this->whenLoaded('enseignants')),

        ];
    }
}
