<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClasseRequest extends FormRequest
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
        $requiredIfUpdate = Rule::requiredIf($request->routeIs('*.update'));
        $requiredIfStore = Rule::requiredIf($request->routeIs('*.store'));

        return [
            'label' => [ $requiredIfStore, "string"],
            'niveau_id' => [ $requiredIfStore, 'integer'],
            'filiere_id' => [ $requiredIfStore, 'integer'],
            'coordinateur_id' => ['integer'],
            'teachers' => ['array'],
            'teachers.*' => ['array'],
            'teachers.*.modules' => ['present', 'array'],
            // 'teachers.*.modules.*' => ['integer'],
            'teachers.*.id' => ['required', 'integer'],
            'modules' => ['array'],
            // 'modules.*' => ['integer'],
 

        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(apiError(errors: $validator->errors()));
    }

}
