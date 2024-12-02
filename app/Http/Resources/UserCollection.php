<?php

namespace App\Http\Resources;

use App\Enums\roleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{



    private $currentYearId = null;
    private $seance = null;
    private $roleLabel = null;

    public function setSeance($seance)
    {
        $this->seance = $seance;
        return $this;
    }
    public function setCurrentYear($currentYearId)
    {
        $this->currentYearId = $currentYearId;
        return $this;
    }
    public function setRoleLabel(roleEnum $roleLabel)
    {
        $this->roleLabel = $roleLabel->value;
        return $this;
    }
    public function toArray(Request $request)
    {
        return $this->collection->map(function ($user) {
            return new UserResource($user, $this->seance, $this->currentYearId, $this->roleLabel);
        });
    }
}
