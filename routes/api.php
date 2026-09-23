<?php

use App\Http\Controllers\GgrGoldApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// NexusGGR Webhook Callbacks
Route::post('/ggr-gold/callback', [GgrGoldApiController::class, 'handleCallback']);
Route::post('/ggr/callback', [GgrGoldApiController::class, 'handleCallback']);
Route::post('/gold_api', [GgrGoldApiController::class, 'handleCallback']);
