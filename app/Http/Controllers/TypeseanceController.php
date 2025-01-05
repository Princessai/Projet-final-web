<?php

namespace App\Http\Controllers;

use App\Models\TypeSeance;
use Illuminate\Http\Request;

class TypeseanceController extends Controller
{
    public function getTypeSeances()
    {
        $response = TypeSeance::all();
        return apiSuccess($response);
    }
}
