<?php

namespace App\Http\Controllers;

use App\Enums\crudActionEnum;
use App\Models\Classe;
use App\Models\Seance;
use App\Models\Absence;
use App\Models\Timetable;
use Ramsey\Uuid\Type\Time;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\seanceStateEnum;
use App\Services\AnneeService;
use App\Services\SeanceService;
use App\Http\Resources\SeanceResource;
use App\Http\Requests\TimeTableRequest;
use App\Http\Resources\TimetableResource;
use App\Models\Droppe;
use Illuminate\Support\Facades\Validator;


include_once(base_path('utilities/seeder/seanceDuration.php'));

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(TimeTableRequest $request)
    {


        // $validator = Validator::make($request->all(), [
        //     'timetable' => 'required|array',
        //     'timetable.classe_id' => 'required|integer',
        //     'timetable.date_debut' => 'required|date',
        //     'timetable.date_fin' => 'required|date',
        //     'timetable.commentaire' => 'nullable|string',
        //     'timetable.params' => 'array',
        //     'seances' => 'required|array',
        //     'seances.*' => 'array',
        //     'seances.*.heure_debut' => 'required|date',
        //     'seances.*.date' => 'required|date',
        //     'seances.*.heure_fin' => 'required|date',
        //     'seances.*.salle_id' => 'required|integer',
        //     'seances.*.module_id' => 'required|integer',
        //     'seances.*.user_id' => 'required|integer',
        //     'seances.*.type_seance_id' => 'required|integer',


        // ]);

        // if ($validator->fails()) {
        //     return  apiError(errors: $validator->errors());
        // }

        // $currentYear = (new AnneeService)->getCurrentYear();


        // $validatedData =   $validated = $request->validated();;
        // $timetableData = $validatedData['timetable'];
        // $seancesData = $validatedData['seances'];

        // $newTimetable = Timetable::create([
        //     'classe_id' => $timetableData['classe_id'],
        //     'date_debut' => $timetableData['date_debut'],
        //     'date_fin' => $timetableData['date_fin'],
        //     'commentaire' => $timetableData['commentaire'],
        //     'annee_id' => $currentYear->id,
        // ]);

        // $seancesData = collect($seancesData)->map(function ($seance) use ($currentYear, $timetableData, $newTimetable) {

        //     $seance['classe_id'] = $timetableData['classe_id'];
        //     $seance['annee_id'] = $currentYear->id;
        //     $seance['timetable_id'] = $newTimetable->id;
        //     $seance['etat'] = seanceStateEnum::ComingSoon->value;


        //     return $seance;
        // });





        // $newTimetable->seances()->createMany($seancesData);
        // return apiSuccess(message: 'Timetable created successfully', data: new TimetableResource($newTimetable));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeTableRequest $request)
    {
        $currentYear = app(AnneeService::class)->getCurrentYear();


        $validatedData = $request->validated();
        $step = 1;

        $timetableData = $validatedData['timetable'];

        $seancesData = $validatedData['seances'];

        $newTimetable = Timetable::create([
            'classe_id' => $timetableData['classe_id'],
            'date_debut' => $timetableData['date_debut'],
            'date_fin' => $timetableData['date_fin'],
            'commentaire' => $timetableData['commentaire'] ?? null,
            'annee_id' => $currentYear->id,
        ]);


        $seancesData = collect($seancesData)->map(function ($seance) use ($currentYear, $timetableData, $newTimetable, $step) {

            $duree = seanceDuration($seance['heure_fin'], $seance['heure_debut'], $step);
            $dureeRaw = seanceDuration($seance['heure_fin'], $seance['heure_debut'], ceil: false);

            $seance['classe_id'] = $timetableData['classe_id'];
            $seance['annee_id'] = $currentYear->id;
            $seance['duree'] = $duree;
            $seance['duree_raw'] = $dureeRaw;
            // $seance['timetable_id'] = $newTimetable->id;
            $seance['etat'] = seanceStateEnum::ComingSoon->value;

            return $seance;
        });



        $newTimetable->seances()->createMany($seancesData);

        return apiSuccess(message: 'Timetable created successfully', data: new TimetableResource($newTimetable));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $timetable = apiFindOrFail(Timetable::with(['seances' => [
            'classe' => ['coordinateur'],
            'module',
            'salle',
            'typeSeance'
        ]]), $id, 'no such timetable');

        $response = new TimetableResource($timetable);

        return apiSuccess(data: $response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TimeTableRequest $request, string $id)
    {
        $validatedData = $request->validated();
        $step = 1;

        $SeanceService = new SeanceService;

        $currentYear =  app(AnneeService::class)->getCurrentYear();

        if ($request->filled('timetable') && !empty($timetableData)) {
            $timetable = new Timetable;
            $timetableData = Arr::except($request->input('timetable'), ['classe_id', 'id', 'annee_id']);

            $timetable = apiFindOrFail($timetable, $id, 'no such timetable');

            $timetable->update($timetableData);
        }

        if ($request->filled('seances')) {

            $seancesData = $validatedData['seances'];

            foreach ($seancesData as $key => $seance) {
                $crudAction = $seance['action'];

                if (isset($seance['id'])) {
                    $seance_id = $seance['id'];
                }

                if ($crudAction == crudActionEnum::Create->value) {
                    $duree = seanceDuration($seance['heure_fin'], $seance['heure_debut'], $step);
                    $dureeRaw = seanceDuration($seance['heure_fin'], $seance['heure_debut'], ceil: false);
                }


                if ($crudAction == crudActionEnum::Update->value) {


                    $seance = Arr::except($seance, ['timetable_id', 'classe_id']);

                    $oldSeance = apiFindOrFail(new Seance, $seance_id, 'no such seance');

                    $oldSeanceState = $oldSeance->etat;

                    $seanceStart = isset($seance['heure_debut']) ? $seance['heure_debut'] : $oldSeance->heure_debut;
                    $seanceEnd = isset($seance['heure_fin']) ? $seance['heure_fin'] : $oldSeance->heure_fin;
                    $duree = seanceDuration($seanceEnd,  $seanceStart, $step);
                    $dureeRaw = seanceDuration($seanceEnd, $seanceStart, ceil: false);
                    $seance['duree'] = $duree;
                    $seance['duree_raw'] = $dureeRaw;


                    if (isset($seance['etat']) && $oldSeanceState !== $seance['etat']) {
                        $seanceState =  $seance['etat'];

                        if ($oldSeanceState == seanceStateEnum::Done->value && $seanceState != seanceStateEnum::ComingSoon->value) {
                            $SeanceService->incrementOrDecrementWorkedHours($oldSeance, $currentYear->id, -1); // decrement ici

                            $oldSeance->absences()->delete();

                            $oldSeance->delays()->delete();

                            Droppe::where([
                                'annee_id' => $currentYear->id,
                                'module_id' => $oldSeance->module_id,
                                'updated_at' => $oldSeance->heure_debut,
                                'isDropped' => true,
                            ])->update(['isDropped' => false]);
                        }
                        if ($seanceState == seanceStateEnum::ComingSoon->value && $oldSeanceState == seanceStateEnum::Done->value) {
                            return apiError(errors: ["The seances.$key.state field can not have this value."]);
                        }
                    }


                    if ($oldSeance->attendance == true) {

                        $seance = Arr::only($seance, ['etat']);
                    }


                    $oldSeance->update($seance);
                }


                if ($crudAction == crudActionEnum::Delete->value) {
                    Seance::destroy($seance_id);
                }

                if ($crudAction == crudActionEnum::Create->value) {
                    if (!isset($timetable)) {
                        $timetable = new Timetable;
                        $timetable = apiFindOrFail($timetable, $id, 'no such timetable');
                    }

                    $seance = Arr::except($seance, ['etat']);
                    $seance['timetable_id'] = $id;


                    $seance['duree'] = $duree;
                    $seance['duree_raw'] = $dureeRaw;


                    $seance['classe_id'] = $timetable->classe_id;
                    $seance['annee_id'] = $timetable->annee_id;


                    Seance::create($seance);
                }
            }
        }

        return apiSuccess(message: 'timetable updated successfully !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        Timetable::destroy($id);

        return apiSuccess(message: 'timetable deleted successfully !');
    }


    public function getClasseTimetables(Request $request, $classe_id, $annee_id, $interval = null)
    {
        $requestData = $request->route()->parameters() + $request->query();

        $validator = Validator::make($requestData, [
            'withSeances' =>  function ($attribute, $value, $fail) {


                $trimedValue = strtolower(str_replace(' ', '', $value));
                if (is_bool($trimedValue) || $trimedValue == 'true' || $trimedValue == 'false') return;
                $fail("The $attribute must be a boolean or a truthy string ('true', 'false').");
            },
            'annee_id' => 'integer',
            'interval' => 'nullable|in:0,1,-1'
        ]);

        if ($validator->fails()) {
            return  apiError(errors: $validator->errors());
        }
        $withSeances = $request->boolean('withSeances', true);


        $classe = Classe::with(['timetables' => function ($query) use ($interval, $annee_id, $withSeances) {

            $now = now();


            switch (true) {
                case $interval === '0':
                    $query->where('annee_id', $annee_id);
                    break;

                case $interval === "1":

                    $query->where('date_fin', '>=', $now);

                    break;

                case $interval === "-1":

                    $query->where('date_fin', '<', $now);
                    break;

                default:

                    $query->where('date_fin', '>=', $now)->orderBy('created_at')->take(1);
                    break;
            }
            if ($withSeances) {
                $query->with(['seances' => ['typeSeance', 'module', 'salle', 'enseignant']]);
            }
        }]);
        $classe = apiFindOrFail($classe, $classe_id);


        //     case $interval === '0':
        //         $yearTimetables = $classe->timetables()->where('annee_id', $annee_id)->get();
        //         break;

        //     case $interval === "1":
        //         $now = now();
        //         $yearTimetables = $classe->timetables()->where('date_fin', '>=', $now)->get();

        //         break;

        //     case $interval === "-1":
        //         $now = now();
        //         $yearTimetables = $classe->timetables()->where('date_fin', '<', $now)->get();
        //         break;

        //     default:
        //         $now = now();
        //         $yearTimetables = $classe->timetables()->where('date_fin', '>=', $now)->orderBy('created_at')->first();
        //         break;
        // }

        $yearTimetables = $classe->timetables;

        if ($interval === null) {
            if ($yearTimetables->isEmpty()) {
                return apiError(message: 'no  current timetable for this class');
            }
            $response = new TimetableResource($yearTimetables->first());
        } else {

            $response = TimetableResource::collection($yearTimetables);
        }

        return apiSuccess(data: $response);
    }

    public function getTimetable(Request $request, $timetable_id)
    {

        $timetable = Timetable::with(['seances' => ['typeSeance', 'module', 'salle', 'enseignant']]);

        $data = apiFindOrFail($timetable, $timetable_id);
        
        $response = new TimetableResource($data);

        return apiSuccess(data: $response);
    }
}
