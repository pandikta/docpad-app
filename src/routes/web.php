<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\FunctionAuthController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::patch('/documents/{document}/rename', [DocumentController::class, 'rename'])->name('documents.rename');
    Route::put('/documents/{document}/teams', [DocumentController::class, 'assignTeams'])->name('documents.assign-teams');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings (Khusus Superadmin)
    Route::middleware('superadmin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/function-auth', [FunctionAuthController::class, 'index'])->name('function-auth.index');
        Route::put('/function-auth', [FunctionAuthController::class, 'update'])->name('function-auth.update');
        Route::delete('/function-auth/reset-user', [FunctionAuthController::class, 'resetUser'])->name('function-auth.reset-user');
    });
});

require __DIR__.'/auth.php';
