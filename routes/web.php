<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Http\Controllers\Web\User\UserCatalogueController;
use App\Http\Controllers\Web\User\UserController;
use App\Http\Controllers\Web\Permission\PermissionController;
use App\Http\Controllers\Web\Team\TeamController;
use App\Http\Controllers\Web\Unit\UnitController;
use App\Http\Controllers\Web\Department\DepartmentController;
use App\Http\Controllers\Web\Task\TaskController;
use App\Http\Controllers\Web\Evaluation\EvaluationController;
use App\Http\Controllers\Web\Status\StatusController;
use App\Http\Controllers\Web\Statistic\StatisticController;
use App\Http\Controllers\Web\Ajax\DashboardController as AjaxDashboardController;
use App\Http\Controllers\Web\Ajax\LocationController as AjaxLocationController;
use App\Http\Controllers\Web\Ajax\EvaluationController as AjaxEvaluationController;

Route::middleware(['noAuth'])->group(function(){
    Route::get('admin', [AuthController::class, 'index'])->name('auth.login');
    Route::post('signin', [AuthController::class, 'signin'])->name('auth.signin');    
});

Route::middleware(['auth'])->group(function(){
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('signout', [AuthController::class, 'signout'])->name('auth.signout');

    Route::middleware(['permission'])->group(function(){

        Route::get('user_catalogues/{id}/delete', [UserCatalogueController::class, 'delete'])->name('user_catalogues.delete');
        Route::resource('user_catalogues', UserCatalogueController::class);

        Route::get('users/{id}/delete', [UserController::class, 'delete'])->name('users.delete');
        Route::resource('users', UserController::class);

        Route::get('permissions/{id}/delete', [PermissionController::class, 'delete'])->name('permissions.delete');
        Route::resource('permissions', PermissionController::class);

        Route::get('teams/{id}/delete', [TeamController::class, 'delete'])->name('teams.delete');
        Route::resource('teams', TeamController::class);

        Route::get('units/{id}/delete', [UnitController::class, 'delete'])->name('units.delete');
        Route::resource('units', UnitController::class);

        Route::get('departments/{id}/delete', [DepartmentController::class, 'delete'])->name('departments.delete');
        Route::resource('departments', DepartmentController::class);

        Route::get('tasks/{id}/delete', [TaskController::class, 'delete'])->name('tasks.delete');
        Route::resource('tasks', TaskController::class);

        Route::get('evaluations/teams/{level}', [EvaluationController::class, 'teams'])->name('evaluations.teams');
        
        Route::get('evaluations/{id}/delete', [EvaluationController::class, 'delete'])->name('evaluations.delete');
        Route::resource('evaluations', EvaluationController::class);

        Route::get('statuses/{id}/delete', [StatusController::class, 'delete'])->name('statuses.delete');
        Route::resource('statuses', StatusController::class);


        Route::get('statistics/departmentMonth/{level}', [StatisticController::class, 'evaluationStatisticMonth'])->name('statistics.department.month');
        Route::get('statistics/departmentDay/{level}', [StatisticController::class, 'leaderEvaluationStatisticDay'])->name('statistics.department.day');
        Route::get('team/export', [StatisticController::class, 'exportHistory'])->name('statistics.exportHistory');

        Route::get('statistics/departmentMonth/leader/{level}', [StatisticController::class, 'leaderStatisticMonth'])->name('statistics.leader.month');
        Route::get('statistics/departmentDay/leader/{level}', [StatisticController::class, 'leaderStatisticDay'])->name('statistics.leader.day');

        
    });

    Route::get('temp/{filename}', function ($filename) {
        $path = sys_get_temp_dir() . '/' . $filename;
        if (file_exists($path)) {
            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }
        return response()->json(['status' => 'error', 'message' => 'File not found'], 404);
    })->name('temp.download');

    Route::put('evaluations/evaluate/{evaluate}', [AjaxEvaluationController::class, 'evaluate'])->name('evaluations.evaluate');
    /*Ajax*/
    
    Route::post('ajax/statistics/export', [AjaxEvaluationController::class, 'export'])->name('statistics.export');
    Route::post('ajax/statistics/exportHistory', [AjaxEvaluationController::class, 'exportHistory'])->name('statistics.exportHistory');
    Route::post('ajax/dashboard/changeStatus', [AjaxDashboardController::class, 'changeStatus'])->name('ajax.dashboard.changeStatus');
    Route::get('ajax/location/getLocation', [AjaxLocationController::class, 'getLocation'])->name('ajax.location.index');
    Route::get('ajax/evaluation/getDepartment', [AjaxEvaluationController::class, 'getDepartment'])->name('ajax.evaluation.getDepartment');
    Route::get('ajax/evaluation/getDepartmentDay', [AjaxEvaluationController::class, 'getDepartmentEvaluationHistory']);
    Route::post('ajax/evaluation/setPoint', [AjaxEvaluationController::class, 'setPoint'])->name('ajax.evaluation.setPoint');
    Route::get('ajax/evaluation/getVice', [AjaxEvaluationController::class, 'getVice'])->name('ajax.evaluation.getVice');
    Route::get('ajax/evaluation/getOfficer', [AjaxEvaluationController::class, 'getOfficer'])->name('ajax.evaluation.getOfficer');
});
