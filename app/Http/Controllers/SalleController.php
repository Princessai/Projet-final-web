<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;

class SalleController extends Controller
{
    public function getSalles()
    {
        $salles = Salle::all();
        return apiSuccess($salles);
    }
}
