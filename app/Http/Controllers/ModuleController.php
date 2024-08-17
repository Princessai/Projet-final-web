<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use App\Http\Resources\ModuleResource;

class ModuleController extends Controller
{
    public function getAllModules()
    {
        $response = ModuleResource::collection(Module::all());
        return apiSuccess($response);
    }
}
