<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use App\Enums\crudActionEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class UserRequestcopy extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        


        // $requiredIfUpdate = Rule::requiredIf($request->routeIs('*.update'));
        
        // $requiredIfUpdate = Rule::requiredIf($request->routeIs('*.update'));
        $requiredIfStore = Rule::requiredIf($request->routeIs('*.store'));
        // $requiredIfClasseAction =  Rule::requiredIf(fn () => $request->routeIs('*.update') && $request->filled('classe_action'));

        $baseRules = [
            'name' => [$requiredIfStore, 'string'],
            'lastname' => [$requiredIfStore, 'string'],
            'email' => [$requiredIfStore, 'email', 'unique:users,email'],
            'phone_number' => [$requiredIfStore],
            'picture' => ['nullable','file','mimes:jpg,jpeg','max:10240'],
        ];

        // $specificUserRules = [];
        // $route = $request->route();

        // if ($route->named('student.store')|| $route->named('student.update')) {

        //     $specificUserRules = [
        //         'classe_id' => [$requiredIfStore, 'integer'],
        //         'classe_action' => [$requiredIfClasseAction, Rule::enum(crudActionEnum::class)],
        //         'parent' => [$requiredIfStore, 'array'],
        //         // 'niveau_id' => [$requiredIfStore, 'integer'],

        //     ];

        //     foreach ($baseRules as $field => $baseRule) {
        //         $specificUserRules["parent.$field"] = $baseRule;
        //     }
        // }

        // if ($route->named('teacher.store') || $route->named('teacher.update')) {
        //     $specificUserRules = [
        //         'classes' => ['array'],
        //         'classes.*' => ['integer'],
        //         'modules' => ['array'],
        //         'modules.*' => ['integer'],
        //     ];
        // }

        // if ($route->named('coordinator.store')|| $route->named('coordinator.update')) {

        //     // $classeElmentTypeRule = $route->named('coordinator.update')?'array':'integer';
           
        //     // $specificUserRules = [
        //     //     'classes' => ['array'],
        //     //     'classes.*' => [$requiredIfStore, $classeElmentTypeRule],
        //     //     // 'classes.*.action' => [Rule::requiredIf(fn () =>  $route->named('coordinator.update') && $request->filled('classes')), Rule::enum(crudActionEnum::class)],
        //     //     // 'classes.*.id'=>[Rule::requiredIf(fn () =>  $route->named('coordinator.update') && $request->filled('classes')), 'integer']
        //     // ];

          

        // }


        return $baseRules ;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(apiError(errors: $validator->errors()));
    }
}
