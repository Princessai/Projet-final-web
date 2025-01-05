<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Module;
use App\Services\AnneeService;
use Illuminate\Http\Request;
use App\Http\Resources\ModuleResource;
use Illuminate\Support\Facades\Validator;

class ModuleController extends Controller
{
    public function getAllModules()
    {
        $response = ModuleResource::collection(Module::all());
        return apiSuccess($response);
    }
    public function getClasseModules(Request $request, $classe_id)
    {

        $requestData = $request->route()->parameters() + $request->query();

        $validator = Validator::make($requestData, [
            'withOthers' =>  function ($attribute, $value, $fail) {


                $trimedValue = strtolower(str_replace(' ', '', $value));
                if (is_bool($trimedValue) || $trimedValue == 'true' || $trimedValue == 'false') return;
                $fail("The $attribute must be a boolean or a truthy string ('true', 'false').");
            },
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }
        $withOthers = $request->boolean('withOthers', false);


        $currentYearId = app(AnneeService::class)->getCurrentYear()->id;


        $query = Classe::with(['modules'=> function($query)use($currentYearId) {
            $query->wherePivot('annee_id', $currentYearId);

        }]);

      
        $classe = apiFindOrFail($query, $classe_id);

        $modulesId = $classe->modules->pluck('id')->all();


        if($withOthers){
            $otherModules = Module::whereNotIn('id', $modulesId)->get();
        }


        if(!$withOthers){
            $response = ModuleResource::collection($classe->modules);

        }else {
            $response = ['classeModules' => ModuleResource::collection($classe->modules),
            'otherModules' => ModuleResource::collection($otherModules)];
        }
        return apiSuccess($response);
    }
}
