<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Tenant\AuthController;
use App\Http\Controllers\Api\Tenant\BillingDocumentController;
use App\Http\Controllers\Api\Tenant\ClientController;
use App\Http\Controllers\Api\Tenant\DocumentTemplateController;
use App\Http\Controllers\Api\Tenant\FinanceController;
use App\Http\Controllers\Api\Tenant\ProjectController;
use App\Http\Controllers\Api\Tenant\QuoteController;
use App\Http\Controllers\Api\Tenant\SettingsController;
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
        Route::patch('/auth/password', [AuthController::class, 'updatePassword']);

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
        Route::post('/quotes/{id}/convert-to-project', [QuoteController::class, 'convertToProject']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{id}', [ProjectController::class, 'show']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{id}/payments', [ProjectController::class, 'registerPayment']);
        Route::post('/projects/{id}/mark-paid', [ProjectController::class, 'markPaid']);
        Route::get('/projects/{id}/payments', [ProjectController::class, 'payments']);
        Route::post('/projects/{id}/complete', [ProjectController::class, 'complete']);
        Route::get('/projects/{id}/billing-documents', [ProjectController::class, 'billingDocuments']);

        Route::get('/billing-documents/{id}/pdf', [BillingDocumentController::class, 'pdf']);
        Route::post('/billing-documents/{id}/mark-sent', [BillingDocumentController::class, 'markSent']);

        Route::get('/finances/summary', [FinanceController::class, 'summary']);
        Route::get('/finances/entries', [FinanceController::class, 'index']);
        Route::post('/finances/entries', [FinanceController::class, 'store']);
        Route::get('/finances/entries/{id}', [FinanceController::class, 'show']);
        Route::patch('/finances/entries/{id}', [FinanceController::class, 'update']);
        Route::delete('/finances/entries/{id}', [FinanceController::class, 'destroy']);

        Route::get('/settings', [SettingsController::class, 'show']);
        Route::patch('/settings', [SettingsController::class, 'update']);

        Route::get('/document-templates', [DocumentTemplateController::class, 'index']);
        Route::put('/document-templates/{id}', [DocumentTemplateController::class, 'update']);
        Route::post('/document-templates/preview', [DocumentTemplateController::class, 'preview']);
    });
});
