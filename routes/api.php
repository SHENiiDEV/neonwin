<?php

use App\Http\Controllers\FlexrixCallbackController;
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

Route::post('/flexrix/casino/callback', [FlexrixCallbackController::class, 'casino'])->name('flexrix.casino.callback');
Route::post('/flexrix/sportsbook/{action}', [FlexrixCallbackController::class, 'sportsbook'])->whereIn('action', ['balance', 'debit', 'credit', 'rollback'])->name('flexrix.sportsbook.callback');
