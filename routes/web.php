<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChronoFrontController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AccountController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| Account Management Routes (Admin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('accounts')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('/', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
    Route::put('/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::post('/{account}/toggle', [AccountController::class, 'toggleStatus'])->name('accounts.toggle');
    Route::delete('/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
});

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
