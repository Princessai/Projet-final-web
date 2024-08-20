<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pauses = require(base_path('data/pauses.php'));


        return [
            'id' => $this->id,
            'pauses' => $pauses,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'commentaire' => $this->commentaire,
            'classe_id' => $this->classe_id,
            'seances'=> SeanceResource::collection($this->seances),
        ];
    }
}
