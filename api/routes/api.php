<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Central\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central (landlord) API — no tenant context
|--------------------------------------------------------------------------
*/

Route::prefix('v1/central')->group(function (): void {
    Route::get('/health', HealthController::class);
});
