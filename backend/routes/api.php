<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API routes follow RESTful URI versioning standards (/api/v1/...).
| Unversioned endpoints (/api/...) are preserved as aliases for
| backward compatibility and seamless client integration.
|
*/

$registerRoutes = function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Protected routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Users list for assignee selection
        Route::get('/users', [UserController::class, 'index']);

        // Task CRUD endpoints
        Route::apiResource('tasks', TaskController::class);
    });
};

Route::prefix('v1')->group($registerRoutes);
$registerRoutes();
