<?php

use App\Models\CourseHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\SeanceController;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\CourseHourController;

Route::prefix('trackin')->group(function () {

    Route::post("/login", [UserController::class, 'login']);



    // groupe de routes authentifiés
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // methode get
        Route::get("/logout", [UserController::class, 'logout']);

        Route::get("/logged_user/infos", [UserController::class, 'loggedUserInfos']);

        Route::get("/user/seances/{user_id}/{timestamp?}", [UserController::class, 'getUserSeances'])
            ->whereNumber(['user_id']);

        Route::get("/timetable/{classe_id}/{annee_id}/{interval?}", [TimetableController::class, 'getClasseTimetables'])
            ->whereNumber(['classe_id', 'annee_id']);

        Route::get("/gethours/classe/year_segments/{classe_id}/{year_segments}", [CourseHourController::class, 'getClasseWorkedHours']);

        Route::get("/gethours/classes/year_segments/{year_segments}", [CourseHourController::class, 'getAllClassesWorkedHours']);



        // faire l'appel 
        Route::prefix("/attendance-record")->group(function () {

            // faire l'appel; persister les données de l'appel 
            Route::post("/create/{seance_id}", [SeanceController::class, 'makeClasseCall']);
            // afficher le relevé avec ses données précharger pour editer
            Route::get("/edit/{seance_id}", [SeanceController::class, 'editClasseCall']);
            // afficher un nouveau relevé de présence vide
            Route::get("/show/{seance_id}", [ClasseController::class, 'getStudentsAttendanceRecord'])
                ->whereNumber('seance_id');

            Route::post("/update/{seance_id}", [SeanceController::class, 'updateClasseCall']);
        });






        // les taux de présence
        Route::prefix("/presence")->group(function () {

            Route::get("/student/{student_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getStudentAttendanceRate'])
                ->whereNumber(['student_id']);;

            Route::get("/students/classe/{classe_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getClassseStudentsAttendanceRate'])
                ->whereNumber(['classe_id']);

            Route::get("/classes/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getClasssesAttendanceRate']);

            Route::get("/student/module/{student_id}/{module_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getModuleAttendanceRate'])
                ->whereNumber(['student_id', 'module_id']);

            Route::get("/students/classe/module/{classe_id}/{module_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getClasseModuleAttendanceRate'])
                ->whereNumber(['classe_id', 'module_id']);

            Route::get("/student/year_segment/{student_id}/{year_segments?}", [AbsenceController::class, 'getStudentAttendanceRateByYearSegment'])
                ->whereNumber(['student_id']);

            // Route::get("/student/months/{student_id}/{months}/{month_count?}", [AbsenceController::class, 'getStudentAttendanceRateByMonth'])
            // ->whereNumber(['student_id', 'months']);;
        });

        // toutes les liste
        Route::prefix('list')->group(function () {


            Route::get("/modules", [ModuleController::class, 'getAllModules']);

            Route::get("/classes", [ClasseController::class, 'getAllClasses']);

            Route::get("/students/classe/{classe_id}", [ClasseController::class, 'getAllClasseStudents'])
                ->whereNumber('classe_id');

            Route::get("/teachers", [UserController::class, 'getAllTeachers']);

            Route::get("/absences/student/{student_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getStudentAbsences'])
                ->whereNumber(['student_id']);;

            Route::get("teachers/{classe_id}", [ClasseController::class, 'getClasseTeachers'])
                ->whereNumber('classe_id');

            Route::get("/parent/children/{parent_id}", [UserController::class, 'getParentsChildren'])
                ->whereNumber('parent_id');
        });
    });
});
