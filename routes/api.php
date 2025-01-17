<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentOfficialController;
use App\Http\Controllers\EmployeeController;
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
    Route::post('/updateDocumentOfficial', [EmployeeController::class, 'updateDocumentOfficial']);
    Route::get('/checkDocument/{formSessionId}', [EmployeeController::class, 'getDocumentBySessionId']);
    Route::get('/getPeriodes', [EmployeeController::class, 'getPeriodes']);
    Route::get('/getOfficialsByPeriode/{periode}', [EmployeeController::class, 'getOfficialsByPeriode']);

    Route::post('/addDocument', [EmployeeController::class, 'addDocument']);
    Route::put('/updateDocument/{id}', [EmployeeController::class, 'updateDocument']);

    Route::post('/addContract', [EmployeeController::class, 'addContract']);
    Route::put('/updateContract/{id}', [EmployeeController::class, 'updateContract']);
    Route::delete('/deleteContract/{id}', [EmployeeController::class, 'deleteContract']);

    Route::get('/session-data/{sessionId}', [EmployeeController::class, 'getSessionData']);
    Route::get('/document-data/{nomorKontrak}', [EmployeeController::class, 'getDocumentData']);
    Route::get('/showImage/{id}', [EmployeeController::class, 'showImage']);

    // Vendor routes
    Route::post('/add-vendor', [VendorController::class, 'addVendor']);
    Route::put('/update-vendor', [VendorController::class, 'updateVendor']);
    Route::get('/get-vendor', [VendorController::class, 'getVendorData']);

    // Official routes
    Route::post('/add-official', [OfficialController::class, 'addOfficial']);
    Route::put('/update-session/{id}', [OfficialController::class, 'updateSession']);
    Route::put('/update-official-session/{id}', [OfficialController::class, 'updateOfficialSession']);
    Route::put('/update-official/{id}', [OfficialController::class, 'updateOfficial']);
    Route::get('/get-official', [OfficialController::class, 'getOfficialData']);
    Route::get('/get-periode', [OfficialController::class, 'getPeriodes']);
    Route::get('/get-official-by-periode/{id}', [OfficialController::class, 'getOfficialsByPeriode']);

    // Document routes
    Route::post('/add-document', [DocumentController::class, 'addDocument']);
    Route::put('/update-document/{id}', [DocumentController::class, 'updateDocument']);
    Route::get('/get-document', [DocumentController::class, 'getDocumentData']);

    //Contract routes
    Route::post('/add-contract', [ContractController::class, 'addContract']);
    Route::put('/update-contract/{id}', [ContractController::class, 'updateContract']);
    Route::delete('/delete-contract/{id}', [ContractController::class, 'deleteContract']);
    Route::get('/get-contract', [ContractController::class, 'getContractData']);
    Route::post('/complete-form', [ContractController::class, 'completeForm']);
    Route::post('/clear-form', [ContractController::class, 'clearFormSession']);
});
