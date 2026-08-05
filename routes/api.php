<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Mcp\McpServerController;
use Illuminate\Support\Facades\Route;

Route::middleware('api_token')->group(function () {
    Route::get('/analytics/clients', [AnalyticsController::class, 'clients']);
    Route::get('/analytics/plans', [AnalyticsController::class, 'plans']);
    Route::get('/analytics/subscriptions', [AnalyticsController::class, 'subscriptions']);
    Route::get('/analytics/usage-records', [AnalyticsController::class, 'usageRecords']);
});

Route::middleware('mcp.auth')->post('/mcp', [McpServerController::class, 'handle'])->name('mcp.server');
