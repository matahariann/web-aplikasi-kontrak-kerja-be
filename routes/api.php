<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [UserController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [UserController::class, 'logout']);

    // Route::middleware(['role:Operator Prodi'])->group(function () {
    //     Route::post('/tambahMahasiswa', [OperatorController::class, 'tambahMahasiswa']);
    //     Route::get('/operator/authenticated', [OperatorController::class, 'getAuthenticatedOperator']); 
    //     Route::get('/count', [OperatorController::class, 'count']);
    // });
});
