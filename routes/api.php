<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\SeanceController;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\CourseHourController;
use App\Http\Controllers\CoordinatorController;

Route::prefix('trackin')->group(function () {

    Route::post("/login", [UserController::class, 'login']);


    // groupe de routes authentifiés
    Route::middleware(['auth:sanctum'])->group(function () {

        // Route::get('/user', function (Request $request) {
        //     return $request->user();
        // });


        Route::get("/logout", [UserController::class, 'logout']);

        Route::get("/logged_user/infos", [UserController::class, 'loggedUserInfos']);

        Route::get("/teacher/force-delete/{user_id}/{role_label}", [UserController::class, 'forceDeleteUser'])
        ->whereNumber(['user_id']);


        
        // CRUD users
        Route::apiResource('user', UserController::class);
        Route::apiResource('admin', AdminController::class);
        Route::apiResource('parent', ParentController::class);
        Route::apiResource('student', StudentController::class);
        Route::apiResource('teacher', TeacherController::class);
        Route::apiResource('coordinator', CoordinatorController::class);
        Route::apiResource('classe', ClasseController::class);


        Route::post("/update/users/picture/{user_id}", [UserController::class, 'updateUserPicture'])
        ->whereNumber(['user_id']);



            
        Route::get("/user/seances/{user_id}/{timestamp?}", [UserController::class, 'getUserSeances'])
            ->whereNumber(['user_id']);


        // worked hours
        Route::get("/gethours/classe/year_segments/{classe_id}/{year_segments?}", [CourseHourController::class, 'getClasseWorkedHours']);

        Route::get("/gethours/classes/year_segments/{year_segments}", [CourseHourController::class, 'getAllClassesWorkedHours']);


        Route::prefix("/timetable")->group(function () {
            // class timetable
            Route::get("/{classe_id}/{annee_id}/{interval?}", [TimetableController::class, 'getClasseTimetables'])
                ->whereNumber(['classe_id', 'annee_id']);

                Route::get("/{timetable_id}", [TimetableController::class, 'getTimetable'])
                ->whereNumber(['timetable_id']);

        });

        // CRUD timetable
        Route::resource('timetable', TimetableController::class);




        // // faire l'appel 
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



        // // les taux de présence
        Route::prefix("/presence")->group(function () {

            // student
            Route::get("/student/{student_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getStudentAttendanceRate'])
                ->whereNumber(['student_id']);

            Route::get("/student/module/{student_id}/{module_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getModuleAttendanceRate'])
                ->whereNumber(['student_id', 'module_id']);

            Route::get(
                "/student/year_segment/{student_id}/{year_segments?}",
                [AbsenceController::class, 'getStudentAttendanceRateByYearSegment']
            )
                ->whereNumber(['student_id']);


            // classe
            Route::get(
                "/students/classe/{classe_id}/{timestamp1?}/{timestamp2?}",

                [AbsenceController::class, 'getClassseStudentsAttendanceRate']
            )

                ->whereNumber(['classe_id']);

            Route::get(
                "/classes/{timestamp1?}/{timestamp2?}",

                [AbsenceController::class, 'getClasssesAttendanceRate']
            );


            Route::get(
                "/students/classe/module/{classe_id}/{module_id}/{timestamp1?}/{timestamp2?}",

                [AbsenceController::class, 'getClasseModuleAttendanceRate']
            )
                ->whereNumber(['classe_id', 'module_id']);

        Route::get("/student/weeks/{student_id}/{annee_id}/{timestamp1?}/{timestamp2?}", 
        [AbsenceController::class, 'getStudentAttendanceRateByweeks']);

            // Route::get("/student/months/{student_id}/{months}/{month_count?}", [AbsenceController::class, 'getStudentAttendanceRateByMonth'])
            // ->whereNumber(['student_id', 'months']);
        });


        // absences
        Route::post("/justify/absence/{absence_id}", [AbsenceController::class, 'justifyStudentAbsence'])
            ->whereNumber(['absence_id']);


        // toutes les liste
        Route::prefix('list')->group(function () {


            Route::get("/modules", [ModuleController::class, 'getAllModules']);

            //classes
            Route::get("/classes", [ClasseController::class, 'getAllClasses']);

            Route::get("/students/classe/{classe_id}", [ClasseController::class, 'getAllClasseStudents'])
                ->whereNumber('classe_id');

            //student
            Route::get("/absences/student/{student_id}/{timestamp1?}/{timestamp2?}", [AbsenceController::class, 'getStudentAbsences'])
                ->whereNumber(['student_id']);;

            //teachers
            Route::get("/teachers", [UserController::class, 'getAllTeachers']);

            Route::get("teachers/{classe_id}", [ClasseController::class, 'getClasseTeachers'])
                ->whereNumber('classe_id');


            //parent
            Route::get("/parent/children/{parent_id}", [UserController::class, 'getParentsChildren'])
                ->whereNumber('parent_id');
        });
    });
});
