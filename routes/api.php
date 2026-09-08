<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// PUBLIC ROUTE: Anyone can try to log in
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES: You MUST have a valid token to access these
Route::middleware('auth:sanctum')->group(function () {

    // User Management Routes
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/departments/list', [UserController::class, 'getDepartments']);

    Route::post('/users/{id}/avatar', [UserController::class, 'updateAvatar']);
    Route::delete('/users/{id}/avatar', [UserController::class, 'removeAvatar']);
    Route::post('/users/{id}/signature', [UserController::class, 'updateSignature']);
    Route::delete('/users/{id}/signature', [UserController::class, 'removeSignature']);

    // Department Management Routes (SuperAdmin & General)
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/departments/{id}', [DepartmentController::class, 'show']);
    Route::put('/departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
    
    // Department Membership Routes
    Route::post('/departments/{id}/assign-vdg', [DepartmentController::class, 'assignVdg']);
    Route::delete('/departments/{id}/vdg/{userId}', [DepartmentController::class, 'removeVdg']);
    Route::post('/departments/{id}/assign-staff', [DepartmentController::class, 'assignStaff']);
    Route::delete('/departments/{id}/staff/{userId}', [DepartmentController::class, 'removeStaff']);

    // Audit Logs (SuperAdmin, DG, File Dept)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // Core Visibility Feeds
    Route::get('/documents/urgent', [DocumentController::class, 'urgentFeed']);
    Route::get('/departments/inbox', [DocumentController::class, 'departmentInbox']);

    // The 7-Step State Machine Action Routes
    // Phase 1: Upload (File Dept)
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/archive', [DocumentController::class, 'searchArchive']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
    // Phase 2: Assign (DG)
    Route::post('/documents/{id}/direct', [DocumentController::class, 'direct']);
    // Phase 3: Dispatch (File Dept)
    Route::post('/documents/{id}/dispatch', [DocumentController::class, 'dispatch']);
    // Phase 4: Upload Work (Staff)
    Route::post('/documents/{id}/report', [DocumentController::class, 'uploadReport']);
    // Phase 5: VDG Sign (VDG)
    Route::post('/documents/{id}/vdg-sign', [DocumentController::class, 'vdgSign']);
    // Phase 6: Final Sign (DG)
    Route::post('/documents/{id}/dg-sign', [DocumentController::class, 'dgFinalSign']);
    // Phase 7: Archive (File Dept)
    Route::post('/documents/{id}/archive', [DocumentController::class, 'archive']);
    // Reject Pipeline
    Route::post('/documents/{id}/reject', [DocumentController::class, 'reject']);
    Route::post('/documents/{id}/return', [DocumentController::class, 'reject']);

    Route::get('/documents/{id}/download', [DocumentController::class, 'downloadFile']);
    Route::get('/documents/{id}/report/download', [DocumentController::class, 'downloadReportFile']);
    Route::get('/documents/{id}/directive/download', [DocumentController::class, 'downloadDirectiveFile']);
    Route::get('/documents/{id}/vdg-sign/download', [DocumentController::class, 'downloadVdgSignFile']);
    Route::get('/documents/{id}/final-sign/download', [DocumentController::class, 'downloadFinalSignFile']);

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
