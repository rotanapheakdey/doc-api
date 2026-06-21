<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// PUBLIC ROUTE: Anyone can try to log in
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES: You MUST have a valid token to access these
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/departments/list', [UserController::class, 'getDepartments']);

    // Core Visibility Feeds
    Route::get('/documents/urgent', [DocumentController::class, 'urgentFeed']);
    Route::get('/departments/inbox', [DocumentController::class, 'departmentInbox']);

    // The 7-Step State Machine Action Routes
    // Phase 1: Upload (File Dept)
    Route::post('/documents', [DocumentController::class, 'store']);
      Route::get('/documents', [DocumentController::class, 'index']);
    // Phase 2: Assign (DG)
    Route::post('/documents/{id}/direct', [DocumentController::class, 'direct']);
    // Phase 3: Dispatch (File Dept)
    Route::post('/documents/{id}/dispatch', [DocumentController::class, 'dispatch']);
    // Phase 4: Upload Work (VDG)
    Route::post('/documents/{id}/report', [DocumentController::class, 'uploadReport']);
    // Phase 5: VDG Sign (VDG)
    Route::post('/documents/{id}/vdg-sign', [DocumentController::class, 'vdgSign']);
    // Phase 6: Final Sign (DG)
    Route::post('/documents/{id}/dg-sign', [DocumentController::class, 'dgFinalSign']);
    // Phase 7: Archive (File Dept)
    Route::post('/documents/{id}/archive', [DocumentController::class, 'archive']);

    Route::get('/documents/archive', [DocumentController::class, 'searchArchive']);
    Route::get('/documents/{id}/download', [DocumentController::class, 'downloadFile']);

    Route::get('/departments', [DepartmentController::class, 'index']);
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Diagnostic Route
Route::get('/test-header', function (Request $request) {
    return response()->json([
        'received_authorization_header' => $request->header('Authorization'),
        'all_headers' => $request->headers->all()
    ]);
});
