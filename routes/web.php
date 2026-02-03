<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChronoFrontController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (with Tenant DB)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/', [ChronoFrontController::class, 'dashboard'])->name('dashboard');
    Route::get('/events', [ChronoFrontController::class, 'events'])->name('events');
    Route::get('/races', [ChronoFrontController::class, 'races'])->name('races');
    Route::get('/entrants', [ChronoFrontController::class, 'entrants'])->name('entrants');
    Route::get('/entrants/import', [ChronoFrontController::class, 'entrantsImport'])->name('entrants.import');
    Route::get('/waves', [ChronoFrontController::class, 'waves'])->name('waves');
    Route::get('/timing', [ChronoFrontController::class, 'timing'])->name('timing');
    Route::get('/results', [ChronoFrontController::class, 'results'])->name('results');
    Route::get('/categories', [ChronoFrontController::class, 'categories'])->name('categories');
    Route::get('/events/{id}/readers', [ChronoFrontController::class, 'readers'])->name('events.readers');
    Route::get('/screens/speaker', [ChronoFrontController::class, 'speakerScreen'])->name('screens.speaker');
    Route::get('/rfidlive', [ChronoFrontController::class, 'rfidlive'])->name('rfidlive');

    // Database export/import
    Route::get('/database/export', [DatabaseController::class, 'export'])->name('database.export');
    Route::post('/database/import', [DatabaseController::class, 'import'])->name('database.import');
});

// TEMPORAIRE : Voir la config PHP
Route::get('/phpinfo', function() {
    phpinfo();
    die();
});
