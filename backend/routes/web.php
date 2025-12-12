<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is a pure API backend. All web requests redirect to the API
| documentation or return API info.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'version' => '1.0.0',
        'type' => 'API',
        'documentation' => '/api/documentation',
        'health' => '/api/health',
    ]);
});

// Health check endpoint (also available without /api prefix)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
});
