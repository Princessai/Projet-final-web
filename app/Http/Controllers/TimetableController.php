<?php

namespace App\Http\Controllers;

use App\Http\Resources\TimetableResource;
use Throwable;
use App\Models\Annee;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimetableController extends Controller
{
    public function getClasseTimetables(Request $request, $classe_id, $annee_id, $interval = null)
    {

        $validator = Validator::make($request->route()->parameters(), [
            'classe_id' => 'exists:classes,id',
            'annee_id' => 'exists:annees,id',
            'interval' =>'nullable|in:0,1,-1'
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }


        $validated = $validator->validated();
        $classe = Classe::with(['timetables.seances'])->find($classe_id);


        switch (true) {
            case $interval === '0':
                $yearTimetables = $classe->timetables()->where('annee_id', $annee_id)->get();
                break;

            case $interval === "1":
                $now = now();
                $yearTimetables = $classe->timetables()->where('date_fin', '>=', $now)->get();

                break;

            case $interval === "-1":
                $now = now();
                $yearTimetables = $classe->timetables()->where('date_fin', '<', $now)->get();
                break;

            default: 
                $now = now();
                $yearTimetables = $classe->timetables()->where('date_fin', '>=', $now)->orderBy('created_at')->first();
                break;
        }

     
        if ($interval == null) {
            // return apiSuccess(data:$yearTimetables );
            $response = new TimetableResource($yearTimetables);
        } else {

            $response = TimetableResource::collection($yearTimetables);
        }

        return apiSuccess(data: $response);
    }
}
