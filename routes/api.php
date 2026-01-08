<?php

use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('api_token')->group(function () {
    Route::get('/analytics/clients', [AnalyticsController::class, 'clients']);
    Route::get('/analytics/plans', [AnalyticsController::class, 'plans']);
    Route::get('/analytics/subscriptions', [AnalyticsController::class, 'subscriptions']);
    Route::get('/analytics/usage-records', [AnalyticsController::class, 'usageRecords']);
});
