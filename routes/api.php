<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ModuleController;


Route::prefix('trackin')->group(function () {

    Route::post("/login", [UserController::class, 'login']);



    // groupe de routes authentifiés
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get("/logout", [UserController::class, 'logout']);


        // all the lists
        Route::prefix('list')->group(function () {

            Route::get("/modules", [ModuleController::class, 'getAllModules']);

            Route::get("/classes", [ClasseController::class, 'getAllClasses']);

            Route::get("students/{classe_id}", [ClasseController::class, 'getAllClasseStudents'])
                ->whereNumber('classe_id');

            Route::get("/teachers", [UserController::class, 'getAllTeachers']);

            Route::get("teachers/{classe_id}", [ClasseController::class, 'getAllClasseTeachers'])
                ->whereNumber('classe_id');

            Route::get("/parent/children/{parent_id}", [UserController::class, 'getParentsChildren'])
            ->whereNumber('parent_id');
        });
    });
});
