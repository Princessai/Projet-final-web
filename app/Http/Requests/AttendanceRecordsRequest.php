<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\attendanceStateEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AttendanceRecordsRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'attendances' => 'present|array',
            'attendances.*' => 'nullable|array',
            'attendances.*.id' => 'required|integer',
            'attendances.*.isDropped' => 'required|boolean',
            'attendances.*.status'=>['required',Rule::enum(attendanceStateEnum::class),]

        ];
    }


    public function failedValidation(Validator $validator)
    {
            throw new HttpResponseException( apiError(errors:$validator->errors()));
    }


}
