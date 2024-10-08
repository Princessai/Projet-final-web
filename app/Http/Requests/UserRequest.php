<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use App\Enums\crudActionEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class UserRequest extends FormRequest
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
        
     

        $requiredIfStore = Rule::requiredIf($request->routeIs('*.store'));

        $baseRules = [
            'name' => [$requiredIfStore, 'string'],
            'lastname' => [$requiredIfStore, 'string'],
            'email' => [$requiredIfStore, 'email', 'unique:users,email'],
            'phone_number' => [$requiredIfStore],
         
        ];

  

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
