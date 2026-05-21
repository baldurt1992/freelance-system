<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\AuthController;
use App\Http\Controllers\Api\Tenant\ClientController;
use App\Http\Controllers\Api\Tenant\QuoteController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant API — requires subdomain (e.g. personal.localhost)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api/v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::get('/clients/{id}', [ClientController::class, 'show']);
        Route::post('/clients/{id}/avatar', [ClientController::class, 'uploadAvatar']);
        Route::put('/clients/{id}', [ClientController::class, 'update']);
        Route::delete('/clients/{id}', [ClientController::class, 'destroy']);

        Route::get('/quotes', [QuoteController::class, 'index']);
        Route::post('/quotes', [QuoteController::class, 'store']);
        Route::get('/quotes/{id}', [QuoteController::class, 'show']);
        Route::put('/quotes/{id}', [QuoteController::class, 'update']);
        Route::delete('/quotes/{id}', [QuoteController::class, 'destroy']);
        Route::post('/quotes/{id}/send', [QuoteController::class, 'send']);
        Route::post('/quotes/{id}/accept', [QuoteController::class, 'accept']);
        Route::post('/quotes/{id}/reject', [QuoteController::class, 'reject']);
        Route::get('/quotes/{id}/pdf', [QuoteController::class, 'pdf']);
    });
});
