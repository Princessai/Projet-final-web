<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Classe;
use App\Enums\roleEnum;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\crudActionEnum;
use App\Services\UserService;
use App\Services\AnneeService;
use Illuminate\Validation\Rule;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Validator;

class CoordinatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {

        $validatedData = $request->validated();
        
        $validator = Validator::make($request->all(), [
            'classes' => ['array'],
            'classes.*' => ['required','integer'],
        ]);
        
        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        } 
        $UserService = new UserService;

        $coordinatorData  = Arr::except($validatedData, ['classes']);

        ['plainText' => $generatedPassword, 'hash' => $generatedPasswordHash] = $UserService
            ->generatePassword($coordinatorData, roleEnum::Coordinateur);

        $coordinatorData['password'] =  $generatedPasswordHash;


        $newCoordinator = $UserService->createUser($coordinatorData, roleEnum::Coordinateur);

        $newCoordinator->generatedPassword = $generatedPassword;

        $classesIds =  $request->input('classes',[]);

        Classe::whereIn('id', $classesIds)->update(['coordinateur_id'=>$newCoordinator->id]);

        $coordinateurClasses = collect([]);
        foreach ($classesIds as $classesId) {
            $classe = new Classe;
            $classe->id = $classesId;

            $classe->coordinateur_id = $newCoordinator->id;
    
            $coordinateurClasses->push($classe);
        }

        $newCoordinator->setRelation('coordinateurClasses', $coordinateurClasses);


        return apiSuccess(data: $newCoordinator, message: 'coordinator created successfully !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $UserService = new UserService;

        $coordinator = User::with(['coordinateurClasses']);

        $response = $UserService->showUser($coordinator, $id);

        return apiSuccess(data: $response);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, string $id)
    {
        $validatedData = $request->validated();
      

        $validator = Validator::make($request->all(), [
            'classes' => ['array'],
            'classes.*' => ['array'],
            'classes.*.action' => [Rule::requiredIf(fn () => $request->filled('classes')), Rule::enum(crudActionEnum::class)],
            'classes.*.id'=>[Rule::requiredIf(fn () => $request->filled('classes')), 'integer']
        ]);
 
        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        } 

      

        $coordinator = apiFindOrFail(new User, $id, 'no such coordinator');

        $coordinatorData =  Arr::except($validatedData, ['classes']);

        $classes = $request->input('classes',[]);

        $addedClasseIds =[];
        $deletedClasseIds =[];
       
        foreach($classes as $classe ){
            $classeAction= $classe['action'];
            $classeId= $classe['id'];
        
            if($classeAction===crudActionEnum::Create->value){
                $addedClasseIds[]=$classeId;
            }

            if($classeAction===crudActionEnum::Delete->value){
                $deletedClasseIds[]=$classeId;
            }
           
        }
    
        if(!empty($addedClasseIds)){
            Classe::whereIn('id', $addedClasseIds)->update(['coordinateur_id'=>$coordinator->id]);  

        }
        if(!empty($deletedClasseIds)){
            Classe::whereIn('id', $deletedClasseIds)->update(['coordinateur_id'=>null]);
        }

        $coordinator->update($coordinatorData);

        return apiSuccess(message:'coordinator updated successfully !');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        User::destroy($id);

        return apiSuccess(message: 'coordinator removed successfully !');
    }
}
