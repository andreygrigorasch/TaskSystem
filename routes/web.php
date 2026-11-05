<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// Same-origin SPA: session cookies and CSRF middleware protect all API mutations.
Route::prefix('api')->group(function () {
    Route::get('/csrf-cookie', fn () => response()->noContent());
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/employees', [ServiceRequestController::class, 'employees']);
        Route::get('/requests', [ServiceRequestController::class, 'index']);
        Route::post('/requests', [ServiceRequestController::class, 'store']);
        Route::get('/requests/{ticket}', [ServiceRequestController::class, 'show'])->whereNumber('ticket');
        Route::patch('/requests/{ticket}/status', [ServiceRequestController::class, 'status'])->whereNumber('ticket');
        Route::patch('/requests/{ticket}/assignee', [ServiceRequestController::class, 'assignee'])->whereNumber('ticket');
    });
});

Route::view('/{path?}', 'app')->where('path', '(?!api(?:/|$)).*');
