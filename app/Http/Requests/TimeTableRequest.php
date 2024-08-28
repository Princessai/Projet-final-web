<?php

namespace App\Http\Requests;

use App\Enums\crudActionEnum;
use Illuminate\Http\Request;
use App\Enums\seanceStateEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class TimeTableRequest extends FormRequest
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
        $requiredIfStore = Rule::requiredIf($request->route()->named('timetable.store'));
        $requiredIfUpdate = Rule::requiredIf($request->route()->named('timetable.update'));
        $prohibitedIFUpdate = Rule::prohibitedIf($request->route()->named('timetable.update'));

        $state = [seanceStateEnum::ComingSoon->value];

        if ($request->route()->named('timetable.update')) {
            $state = array_filter(seanceStateEnum::cases(), fn($state) => $state !== seanceStateEnum::Done->value);
        }

        $ruleIn = Rule::in($state);

        $requiredIfUpadteUorD =  function (string $attribute, mixed $value,  $fail) {

            if (request()->route()->named('timetable.update') && !isset($value['action'])) {
                $fail("The {$attribute}.action field is required.");
                return;
            }
            if (request()->route()->named('timetable.update') && ($value['action'] === crudActionEnum::Update->value || $value['action'] === crudActionEnum::Delete->value)) {
                if (!isset($value['id'])) {
                    $fail("The {$attribute}.id field is required.");
                }
            }
        };

        $requiredIfUpadteCreate =  function (string $attribute, mixed $value,  $fail) {

            if (request()->route()->named('timetable.update') && !isset($value['action'])) {
                $fail("The {$attribute}.action field is required.");
                return;
            }

            if (request()->route()->named('timetable.update') && ($value['action'] === crudActionEnum::Create->value)) {

                $requiredFields =  ['date', 'heure_debut', 'heure_fin', 'type_seance_id', 'module_id', 'salle_id', 'user_id'];

                $isMissingRequiredField = false;
                $missingFields = '';
                foreach ($requiredFields as $requiredField) {
                    if (!isset($value[$requiredField])) {
                        $isMissingRequiredField = true;
                        $missingFields = $requiredField;
                        break;
                    }
                }

                if ($isMissingRequiredField) {
                    $fail("The {$attribute}.$missingFields field is required.");
                }
            }
        };

        return [
            'timetable' => [$requiredIfStore, 'array'],
            'timetable.classe_id' => [$requiredIfStore, 'integer'],
            'timetable.date_debut' => [$requiredIfStore, 'date'],
            'timetable.date_fin' => [$requiredIfStore, 'date'],
            'timetable.commentaire' => 'nullable|string',
            'timetable.params' => 'array',
            'seances' => [$requiredIfStore, 'array'],
            'seances.*' => ['array', $requiredIfUpadteCreate, $requiredIfUpadteUorD],
            'seances.*.action' => ['nullable', Rule::enum(crudActionEnum::class)],
            'seances.*.id' => ['integer'],
            'seances.*.heure_debut' => [$requiredIfStore, 'date'],
            'seances.*.date' => [$requiredIfStore, 'date'],
            'seances.*.heure_fin' => [$requiredIfStore, 'date'],
            'seances.*.attendance' => ['boolean'],
            'seances.*.salle_id' => [$requiredIfStore, 'integer'],
            'seances.*.module_id' => [$requiredIfStore, 'integer'],
            'seances.*.user_id' => [$requiredIfStore, 'integer'],
            'seances.*.type_seance_id' => [$requiredIfStore, 'integer'],
            'seances.*.etat' => ['nullable', $ruleIn],
            'seances.*.classe_id' => 'integer',
            'seances.*.timetable_id' => 'integer',
            // 'seances.*.classe_id' => $prohibitedIFUpdate,
            // 'seances.*.timetable_id' => $prohibitedIFUpdate,



        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(apiError(errors: $validator->errors()));
    }
}
