<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\WasteCollectorController;
use App\Http\Controllers\WasteInvoiceController;
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
Route::get('/waste-collector/{id}', [WasteCollectorController::class, 'showWasteCollector']);
Route::get('/waste-collectors', [WasteCollectorController::class, 'showAllWasteCollector']);
Route::put('/waste-collector/{id}', [WasteCollectorController::class, 'updateWasteCollector']);

// Type routes.
Route::post('/type', [TypeController::class, 'storeType']);
Route::get('/type/{id}', [TypeController::class, 'showType']);
Route::get('/types', [TypeController::class, 'showAllType']);
Route::put('/type/{id}', [TypeController::class, 'updateType']);

// Payment routes.
Route::post('/payment', [PaymentController::class, 'storePayment']);
Route::get('/payment/{id}', [PaymentController::class, 'showPayment']);
Route::get('/payments', [PaymentController::class, 'showAllPayment']);
// Route::put('/payment/{id}', [PaymentController::class, 'updatePayment']);

// Location routes.
Route::post('/location', [LocationController::class, 'storeLocation']);
Route::get('/location/{id}', [LocationController::class, 'showLocation']);
Route::get('/locations', [LocationController::class, 'showAllLocation']);
Route::put('/location/{id}', [LocationController::class, 'updateLocation']);

// Supervisor routes.
Route::post('/supervisor', [SupervisorController::class, 'storeSupervisor']);
Route::get('/supervisor/{id}', [SupervisorController::class, 'showSupervisor']);
Route::get('/supervisors', [SupervisorController::class, 'showAllSupervisor']);
Route::put('/supervisor/{id}', [SupervisorController::class, 'updateSupervisor']);

// Collection routes.
Route::post('/collection', [CollectionController::class, 'storeCollection']);
Route::get('/collection/{id}', [CollectionController::class, 'showCollection']);
Route::get('/collections', [CollectionController::class, 'showAllCollection']);
Route::put('/collection/{id}', [CollectionController::class, 'updateCollection']);

// Waste Invoice routes.
Route::post('/waste_invoice', [WasteInvoiceController::class, 'storeWasteInvoice']);
Route::get('/waste_invoice/{id}', [WasteInvoiceController::class, 'showWasteInvoice']);
Route::get('/waste_invoices', [WasteInvoiceController::class, 'showAllWasteInvoice']);
Route::put('/waste_invoice/{id}', [WasteInvoiceController::class, 'updateWasteInvoice']);