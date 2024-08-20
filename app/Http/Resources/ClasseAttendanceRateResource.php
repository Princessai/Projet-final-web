<?php

namespace App\Http\Resources;

use App\Services\ClasseService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseAttendanceRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $classeService = new ClasseService;
        $timestamp1 =  $request->route('timestamp1');
        $timestamp2 =  $request->route('timestamp2');

        ['classeAttendanceRate' => $classeAttendanceRate] = $classeService->getClasseAttendanceRates($this, $timestamp1, $timestamp2);

        return [
            'id' => $this->id,
            'label' => $this->label,
            'classeAttendanceRate' => $classeAttendanceRate,
        ];
    }
}
