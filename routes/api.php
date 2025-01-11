<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentOfficialController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
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
    // Autentikasi routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [UserController::class, 'logout']);

    // Employee routes
    Route::get('/employee/authenticated', [EmployeeController::class, 'getAuthenticatedEmployee']);
    Route::post('/addVendor', [EmployeeController::class, 'addVendor']);
    Route::put('/updateVendor/{id}', [EmployeeController::class, 'updateVendor']);
    Route::post('/addOfficial', [EmployeeController::class, 'addOfficial']);
    Route::put('/updateOfficial/{id}', [EmployeeController::class, 'updateOfficial']);
    Route::post('/addDocument', [EmployeeController::class, 'addDocument']);
    Route::put('/updateDocument/{id}', [EmployeeController::class, 'updateDocument']);
    Route::post('/addContract', [EmployeeController::class, 'addContract']);
    Route::put('/updateContract/{id}', [EmployeeController::class, 'updateContract']);
    Route::post('/saveDocumentWithOfficials', [EmployeeController::class, 'saveDocumentWithOfficials']);
    Route::put('/updateDocumentWithOfficials/{nomor_kontrak}', [EmployeeController::class, 'updateDocumentWithOfficials']);
    Route::get('/showImage/{id}', [EmployeeController::class, 'showImage']);

    // Vendor routes
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::get('/vendors/{id}', [VendorController::class, 'show']);
    Route::post('/vendors', [VendorController::class, 'store']);

    // Document routes
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{nomor_kontrak}', [DocumentController::class, 'show']);
    Route::post('/documents', [DocumentController::class, 'store']);
    
    // Official routes
    Route::post('/officials', [OfficialController::class, 'store']);

    // Contract routes
    Route::get('/contracts/{nomor_kontrak}', [ContractController::class, 'show']);
    Route::post('/contracts', [ContractController::class, 'store']);

    // Document Official routes
    Route::post('/document-officials', [DocumentOfficialController::class, 'store']);
    Route::get('/document-officials/document/{nomor_kontrak}', [DocumentOfficialController::class, 'getByDocument']);
    Route::get('/document-officials/official/{nip}', [DocumentOfficialController::class, 'getByOfficial']);
});
