<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\TimetableController;


Route::prefix('trackin')->group(function () {

    Route::post("/login", [UserController::class, 'login']);



    // groupe de routes authentifiés
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get("/logout", [UserController::class, 'logout']);
        
        Route::get("/presence/student/{student_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getStudentAttendanceRate'])
        ->whereNumber(['student_id']);;

        Route::get("/presence/students/classe/{classe_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getClassseStudentsAttendanceRate'])
        ->whereNumber(['classe_id']);;

        Route::get("presence/classes/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getClasssesAttendanceRate']);

        Route::get("/logged_user/infos", [UserController::class, 'loggedUserInfos']);
        
        Route::get("/user/seances/{user_id}/{classe_id}/{timestamp}", [UserController::class, 'getTeacherSeancesClasse'])
        ->whereNumber(['user_id','classe_id','timestamp']);
        
        Route::get("/teacher/seances/{teacher_id}/{classe_id}/{module_id}", [UserController::class, 'getTeacherSeancesClasse'])
        ->whereNumber(['teacher_id','classe_id','timestamp']);
        
        Route::get("/timetable/{classe_id}/{annee_id}/{interval?}", [TimetableController::class, 'getClasseTimetables'])
        ->whereNumber(['classe_id','annee_id']);

        // all the lists
        Route::prefix('list')->group(function () {

            Route::get("/modules", [ModuleController::class, 'getAllModules']);

            Route::get("/classes", [ClasseController::class, 'getAllClasses']);

            Route::get("students/{classe_id}", [ClasseController::class, 'getAllClasseStudents'])
                ->whereNumber('classe_id');

            Route::get("/teachers", [UserController::class, 'getAllTeachers']);


            Route::get("teachers/{classe_id}", [ClasseController::class, 'getClasseTeachers'])
                ->whereNumber('classe_id');

            Route::get("/parent/children/{parent_id}", [UserController::class, 'getParentsChildren'])
                ->whereNumber('parent_id');
        });
    });
});
