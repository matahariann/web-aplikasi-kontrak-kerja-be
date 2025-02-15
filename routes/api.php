<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentOfficialController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FormSessionController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Models\Official;
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

// User routes
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/verify-email', [UserController::class, 'verifyEmail']);
Route::post('/resend-verification', [UserController::class, 'resendVerificationCode']);
Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);
Route::post('/resend-reset-password-code', [UserController::class, 'resendResetPasswordCode']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [UserController::class, 'logout']);

    // Employee routes
    Route::get('/employee/authenticated', [EmployeeController::class, 'getAuthenticatedEmployee']);

    // Vendor routes
    Route::post('/add-vendor', [VendorController::class, 'addVendor']);
    Route::put('/update-vendor', [VendorController::class, 'updateVendor']);
    Route::get('/get-vendor', [VendorController::class, 'getVendorData']);
    Route::delete("/delete-vendor/{id}", [VendorController::class, 'deleteVendor']);

    // Official routes
    Route::post('/add-official', [OfficialController::class, 'addOfficial']);
    Route::put('/update-official/{id}', [OfficialController::class, 'updateOfficial']);
    Route::get('/get-official', [OfficialController::class, 'getOfficialData']);
    Route::get('/get-periode', [OfficialController::class, 'getPeriodes']);
    Route::get('/get-official-by-periode/{id}', [OfficialController::class, 'getOfficialsByPeriode']);

    // Document routes
    Route::post('/add-document', [DocumentController::class, 'addDocument']);
    Route::put('/update-document/{id}', [DocumentController::class, 'updateDocument']);
    Route::get('/get-document', [DocumentController::class, 'getDocumentData']);
    Route::get('/get-data', [DocumentController::class, 'getData']);
    Route::get('/get-data-detail/{nomorKontrak}', [DocumentController::class, 'getDataDetail']);

    //Contract routes
    Route::post('/add-contract', [ContractController::class, 'addContract']);
    Route::put('/update-contract/{id}', [ContractController::class, 'updateContract']);
    Route::delete('/delete-contract/{id}', [ContractController::class, 'deleteContract']);
    Route::get('/get-contract', [ContractController::class, 'getContractData']);

    // Form routes
    Route::post('/complete-form', [FormSessionController::class, 'completeForm']);
    Route::post('/clear-form', [FormSessionController::class, 'clearFormSession']);
    Route::put('/update-session/{id}', [FormSessionController::class, 'updateSession']);
    Route::put('/update-official-session/{id}', [FormSessionController::class, 'updateOfficialSession']);

    // Image routes
    Route::get('/showImage/{id}', [ImageController::class, 'showImage']);
});
