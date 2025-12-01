<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RaceController;
use App\Http\Controllers\Api\WaveController;
use App\Http\Controllers\Api\EntrantController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\RaspberryController;
use App\Http\Controllers\Api\ReaderController;

/*
|--------------------------------------------------------------------------
| API Routes - ChronoFront Laravel
|--------------------------------------------------------------------------
|
| API REST pour l'application de chronométrage sportif
|
*/

// Events Routes
Route::apiResource('events', EventController::class);

// Races Routes
Route::get('races/event/{eventId}', [RaceController::class, 'byEvent']);
Route::post('races/{race}/start', [RaceController::class, 'start']);
Route::put('races/{race}/start', [RaceController::class, 'updateStartTime']);
Route::post('races/{race}/end', [RaceController::class, 'end']);
Route::post('races/update-order', [RaceController::class, 'updateOrder']);
Route::apiResource('races', RaceController::class);

// Waves Routes
Route::get('waves/race/{raceId}', [WaveController::class, 'byRace']);
Route::post('waves/{wave}/start', [WaveController::class, 'start']);
Route::post('waves/{wave}/end', [WaveController::class, 'end']);
Route::post('waves/{wave}/assign-all', [WaveController::class, 'assignAllEntrants']);
Route::apiResource('waves', WaveController::class);

// Categories Routes
Route::post('categories/init-ffa', [CategoryController::class, 'initFFA']);
Route::apiResource('categories', CategoryController::class);

// Entrants Routes
Route::get('entrants/search', [EntrantController::class, 'search']);
Route::post('entrants/import', [EntrantController::class, 'import']);
Route::apiResource('entrants', EntrantController::class);

// Results/Timing Routes
Route::get('results', [ResultController::class, 'index']);
Route::get('results/race/{raceId}', [ResultController::class, 'byRace']);
Route::post('results/time', [ResultController::class, 'addTime']);
Route::post('results/manual-batch', [ResultController::class, 'storeManualBatch']);
Route::post('results/race/{raceId}/recalculate', [ResultController::class, 'recalculatePositions']);
Route::get('results/race/{raceId}/export', [ResultController::class, 'export']);
Route::put('results/{result}', [ResultController::class, 'update']);
Route::delete('results/{result}', [ResultController::class, 'destroy']);

// Readers Routes
Route::get('readers/event/{eventId}', [ReaderController::class, 'byEvent']);
Route::post('readers/event/{eventId}/ping-all', [ReaderController::class, 'pingAll']);
Route::post('readers/{reader}/ping', [ReaderController::class, 'ping']);
Route::apiResource('readers', ReaderController::class);

// RFID Raspberry Reader Routes
Route::put('raspberry', [RaspberryController::class, 'store']);
Route::post('raspberry', [RaspberryController::class, 'store']);

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app' => 'ChronoFront Laravel'
    ]);
});
