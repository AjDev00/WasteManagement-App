<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\EarningController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TempImageController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\WasteCollectorController;
use App\Http\Controllers\WasteInvoiceController;
use App\Models\WasteCollector;
use App\Models\WasteInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//get authenticated users.
Route::get('/resi', function (Request $request) {
    return $request->resident();
})->middleware('auth:resident');

Route::get('/picker', function (Request $request) {
    return $request->wasteCollector();
})->middleware('auth:waste_collector');

Route::get('/super', function (Request $request) {
    return $request->supervisor();
})->middleware('auth:supervisor');

Route::get('/summary/{residentId}', [SummaryController::class, 'weeklySummary']);

Route::get('/completed-stats/{waste_collector_id}', [ReportController::class, 'completedStats']);
Route::get('/completed-daily-this-week', [ReportController::class, 'dailyCompletedThisWeek']);
Route::get('/completed-between', [ReportController::class, 'completedBetween']);

Route::post('/earnings/accepted', [EarningController::class, 'acceptedWaste']);
Route::get('/earnings/{resident_id}', [EarningController::class, 'getEarnings']);

//admin routes.
Route::get('/get-all-resident-details', [AdminController::class, 'getAllResidentDetailsWithInvoicesAndLocation']);
Route::get('/get-specific-resident-details/{residentName}', [AdminController::class, 'getSpecificResidentDetailsWithInvoicesAndLocation']);
Route::get('/get-specific-picker-details/{pickerName}', [AdminController::class, 'getSpecificWasteCollectorWithInvoicesHandled']);
Route::get('/get-all-picker-details', [AdminController::class, 'getAllWasteCollectorWithInvoicesHandles']);

//store temp images.
Route::post('/save-temp-image', [TempImageController::class, 'store']);

//notifications route.
Route::get('/notifications/{resident_id}', [NotificationController::class, 'showNotifications']);
Route::delete('/deleteNot/{id}', [NotificationController::class, 'deleteNotification']);
Route::put('/notification/{id}/read', [NotificationController::class, 'isRead']);
Route::get('/waste-collector-notifications/{waste_collector_id}', [NotificationController::class, 'showWasteCollectorNotifications']);
Route::delete('/waste-collector-notifications/{id}', [NotificationController::class, 'deleteWasteCollectorNotification']);

// Residents routes.
Route::post('/resident', [ResidentController::class, 'storeResident']);
Route::post('/resident/login', [ResidentController::class, 'loginResident']);
Route::get('/resident/{id}', [ResidentController::class, 'showResident']);
Route::get('/residents', [ResidentController::class, 'showAllResidents']);
Route::put('/resident/{id}', [ResidentController::class, 'updateResident']);
Route::post('/logout', [ResidentController::class, 'logout'])->middleware('auth:resident');

// Waste Collectors routes.
Route::post('/waste-collector', [WasteCollectorController::class, 'storeWasteCollector']);
Route::post('/waste-collector/login', [WasteCollectorController::class, 'loginWasteCollector']);
Route::get('/all-collection', [WasteCollectorController::class, 'showAllCollection']); //view all collection available(relation with waste invoices)
Route::get('/waste-collector/{id}', [WasteCollectorController::class, 'showWasteCollector']);
Route::get('/waste-collectors', [WasteCollectorController::class, 'showAllWasteCollector']);
Route::put('/waste-collector/{id}', [WasteCollectorController::class, 'updateWasteCollector']);
Route::post('/logoutW', [WasteCollectorController::class, 'logout'])->middleware('auth:waste_collector');

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
Route::get('/location/check/{resident_id}', [LocationController::class, 'checkIfUserLocationExists']);
Route::get('/location/{id}', [LocationController::class, 'showLocation']);
Route::get('/locations', [LocationController::class, 'showAllLocation']);
Route::put('/location/{id}', [LocationController::class, 'updateLocation']);

// Supervisor routes.
Route::post('/supervisor', [SupervisorController::class, 'storeSupervisor']);
Route::post('/supervisor/login', [SupervisorController::class, 'loginSupervisor']);
Route::post('/logoutS', [SupervisorController::class, 'logout'])->middleware('auth:supervisor');
Route::get('/supervisor/{id}', [SupervisorController::class, 'showSupervisor']);
Route::get('/supervisors', [SupervisorController::class, 'showAllSupervisor']);
Route::put('/supervisor/{id}', [SupervisorController::class, 'updateSupervisor']);

// Collection routes.
Route::post('/collection', [CollectionController::class, 'storeCollection']);
Route::post('/collections/{collection}/attach_picker', [CollectionController::class, 'attachPicker']);
Route::get('/view-collection/{id}', [CollectionController::class, 'viewCollection']); //view a single collection(relation with waste invoices)
Route::get('/new-requests', [CollectionController::class, 'newRequests']);
Route::get('/all-collections', [CollectionController::class, 'viewAllCollections']);
Route::get('/filtervia-location/{locationName}', [CollectionController::class, 'filterCollectionViaLocation']);
Route::get('/ongoing/{waste_collector_id}', [CollectionController::class, 'onGoing']);
Route::put('/collection/{id}/cancel', [CollectionController::class, 'cancelAssignment']);
Route::get('/completed/{waste_collector_id}', [CollectionController::class, 'completed']);
Route::put('/collection/{id}', [CollectionController::class, 'updateCollection']);

// Waste Invoice routes.
Route::post('/waste_invoice', [WasteInvoiceController::class, 'storeWasteInvoice']);
Route::get('/waste_invoice/{resident_id}', [WasteInvoiceController::class, 'showResidentWasteInvoices']);
Route::get('/waste_invoice/{id}/{collection_id}', [WasteInvoiceController::class, 'showWasteInvoice']);
Route::get('/filter_by_type/{type_id}/{resident_id}', [WasteInvoiceController::class, 'filterWasteInvoiceByType']);
Route::get('/first_five_waste_invoices/{resident_id}', [WasteInvoiceController::class, 'showFirstFiveWasteInvoices']);
Route::get('/filter_by_pending/{id}', [WasteInvoiceController::class, 'filterByPendingStatus']);
Route::get('/filter_by_verified/{id}', [WasteInvoiceController::class, 'filterByVerifiedStatus']);
Route::get('/filter_by_delivered/{id}', [WasteInvoiceController::class, 'filterByDeliveredStatus']);
Route::get('/filter_by_paid/{id}', [WasteInvoiceController::class, 'filterByPaidStatus']);
// Route::get('/total_waste_invoice/{resident_id}', [WasteInvoiceController::class, 'getTotalAmountOfWasteInvoices']);
// Route::get('/total_plastic_waste_invoice/{resident_id}', [WasteInvoiceController::class, 'getTotalAmountOfPlasticWasteInvoices']);
// Route::get('/total_ewaste_invoice/{resident_id}', [WasteInvoiceController::class, 'getTotalAmountOfEWasteInvoices']);
// Route::get('/total_organic_waste_invoice/{resident_id}', [WasteInvoiceController::class, 'getTotalAmountOfOrganicWasteInvoices']);
// Route::get('/total_cans_waste_invoice/{resident_id}', [WasteInvoiceController::class, 'getTotalAmountOfCansWasteInvoices']);

Route::get('/waste_invoices', [WasteInvoiceController::class, 'showAllWasteInvoice']);
Route::put('/waste_invoice/{id}', [WasteInvoiceController::class, 'updateWasteInvoice']);

//Feedback Routes.
Route::post('/feedback', [FeedbackController::class, 'storeFeedback']);
Route::get('/feedback/{id}', [FeedbackController::class, 'showFeedback']);
Route::get('/feedbacks', [FeedbackController::class, 'showAllFeedback']);
Route::put('/feedback/{id}', [FeedbackController::class, 'updateFeedback']);

//Messaging Routes.
Route::post('/message', [MessagingController::class, 'storeMessage']);
Route::get('/message/{id}', [MessagingController::class, 'showMessage']);
Route::get('/messages', [MessagingController::class, 'showAllMessage']);
Route::put('/message/{id}', [MessagingController::class, 'updateMessage']);