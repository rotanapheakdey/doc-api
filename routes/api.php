<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// PUBLIC ROUTE: Anyone can try to log in
Route::post('/login',[AuthController::class, 'login']);

// PROTECTED ROUTES: You MUST have a valid token to access these
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::post('/documents/{id}/direct', [DocumentController::class, 'direct']);
    Route::get('/documents/urgent', [DocumentController::class, 'urgentFeed']);
    //logout
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/test-header', function (Request $request) {
    return response()->json([
        'received_authorization_header' => $request->header('Authorization'),
        'all_headers' => $request->headers->all()
    ]);
});
