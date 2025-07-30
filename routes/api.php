<?php

use App\Http\Controllers\ResidentController;
use App\Http\Controllers\WasteCollectorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Residents routes.
Route::post('/resident', [ResidentController::class, 'storeResident']); // Route for creating a resident
Route::get('/resident/{id}', [ResidentController::class, 'showResident']); // Route for showing a single resident by ID
Route::get('/residents', [ResidentController::class, 'showAllResidents']); //Route for showing all residents
Route::put('/resident/{id}', [ResidentController::class, 'updateResident']); // Route for updating a resident by ID

// Waste Collectors routes.
Route::post('/waste-collector', [WasteCollectorController::class, 'storeWasteCollector']); // Route for creating a waste collector