<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\ExtensionController;
use App\Http\Controllers\Settings\FunctionAuthController;
use App\Http\Controllers\Settings\MenuAuthController;
use App\Http\Controllers\Settings\SystemConfigController;
use App\Http\Controllers\Settings\TeamController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TeamControlController;
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
    ->middleware(['auth', 'verified', 'menu.auth:menu.dashboard'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents/bulk-download', [DocumentController::class, 'bulkDownload'])->name('documents.bulk-download');
    Route::post('/documents/bulk-delete', [DocumentController::class, 'bulkDelete'])->name('documents.bulk-delete');
    Route::post('/documents/bulk-folder', [DocumentController::class, 'bulkMoveFolder'])->name('documents.bulk-folder');
    Route::post('/documents/bulk-teams', [DocumentController::class, 'bulkAssignTeams'])->name('documents.bulk-teams');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::patch('/documents/{document}/rename', [DocumentController::class, 'rename'])->name('documents.rename');
    Route::patch('/documents/{document}/visibility', [DocumentController::class, 'updateVisibility'])->name('documents.visibility');
    Route::patch('/documents/{document}/encryption', [DocumentController::class, 'toggleEncryption'])->name('documents.encryption');
    Route::patch('/documents/{document}/favorite', [DocumentController::class, 'toggleFavorite'])->name('documents.favorite');
    Route::patch('/documents/{document}/folder', [DocumentController::class, 'moveFolder'])->name('documents.folder');
    Route::put('/documents/{document}/teams', [DocumentController::class, 'assignTeams'])->name('documents.assign-teams');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::post('/document-folders', [DocumentController::class, 'storeFolder'])->name('document-folders.store');
    Route::delete('/document-folders/{folder}', [DocumentController::class, 'destroyFolder'])->name('document-folders.destroy');
    Route::post('/documents/{document}/share', [DocumentController::class, 'createShareLink'])->name('documents.share.store');
    Route::patch('/documents/{document}/share/password', [DocumentController::class, 'updateSharePassword'])->name('documents.share.password');
    Route::delete('/documents/{document}/share', [DocumentController::class, 'revokeShareLink'])->name('documents.share.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::post('/password/force-update', [PasswordController::class, 'forceUpdate'])->name('password.force-update');

    // Team Control (dikontrol oleh Menu Authorization, Superadmin selalu punya akses penuh)
    Route::middleware('menu.auth:menu.team-control')->group(function () {
        Route::get('/team-control', [TeamControlController::class, 'index'])->name('team-control.index');
        Route::delete('/team-control/share-links/{shareLink}', [TeamControlController::class, 'revokeShareLink'])->name('team-control.share-links.revoke');
        Route::put('/team-control/permissions', [TeamControlController::class, 'updatePermission'])->name('team-control.permissions.update');
        Route::delete('/team-control/permissions/reset', [TeamControlController::class, 'resetPermission'])->name('team-control.permissions.reset');
    });

    Route::middleware('menu.auth:menu.profile')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Settings (dikontrol oleh Menu Authorization, Superadmin selalu punya akses penuh)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('menu.auth:menu.settings.extensions')->group(function () {
            Route::get('/extensions', [ExtensionController::class, 'index'])->name('extensions.index');
            Route::post('/extensions', [ExtensionController::class, 'store'])->name('extensions.store');
            Route::put('/extensions/{extension}', [ExtensionController::class, 'update'])->name('extensions.update');
            Route::delete('/extensions/{extension}', [ExtensionController::class, 'destroy'])->name('extensions.destroy');
        });

        Route::middleware('menu.auth:menu.settings.teams')->group(function () {
            Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
            Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
            Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
            Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
            Route::post('/teams/requests/{teamRequest}/approve', [TeamController::class, 'approveRequest'])->name('teams.requests.approve');
            Route::post('/teams/requests/{teamRequest}/reject', [TeamController::class, 'rejectRequest'])->name('teams.requests.reject');
        });

        Route::middleware('menu.auth:menu.settings.users')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
            Route::post('/users/{user}/send-reset-link', [UserController::class, 'sendResetLink'])->name('users.send-reset-link');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        Route::middleware('menu.auth:menu.settings.function-auth')->group(function () {
            Route::get('/function-auth', [FunctionAuthController::class, 'index'])->name('function-auth.index');
            Route::put('/function-auth', [FunctionAuthController::class, 'update'])->name('function-auth.update');
            Route::delete('/function-auth/reset-user', [FunctionAuthController::class, 'resetUser'])->name('function-auth.reset-user');
            Route::post('/function-auth/functions', [FunctionAuthController::class, 'storeFunction'])->name('function-auth.functions.store');
            Route::put('/function-auth/functions/{appFunction}', [FunctionAuthController::class, 'updateFunction'])->name('function-auth.functions.update');
            Route::delete('/function-auth/functions/{appFunction}', [FunctionAuthController::class, 'destroyFunction'])->name('function-auth.functions.destroy');
        });

        Route::middleware('menu.auth:menu.settings.menu-auth')->group(function () {
            Route::get('/menu-auth', [MenuAuthController::class, 'index'])->name('menu-auth.index');
            Route::put('/menu-auth', [MenuAuthController::class, 'update'])->name('menu-auth.update');
            Route::delete('/menu-auth/reset-user', [MenuAuthController::class, 'resetUser'])->name('menu-auth.reset-user');
            Route::post('/menu-auth/menus', [MenuAuthController::class, 'storeMenu'])->name('menu-auth.menus.store');
            Route::put('/menu-auth/menus/{menu}', [MenuAuthController::class, 'updateMenu'])->name('menu-auth.menus.update');
            Route::delete('/menu-auth/menus/{menu}', [MenuAuthController::class, 'destroyMenu'])->name('menu-auth.menus.destroy');
        });

        Route::middleware('menu.auth:menu.settings.system-config')->group(function () {
            Route::get('/system-config', [SystemConfigController::class, 'index'])->name('system-config.index');
            Route::put('/system-config', [SystemConfigController::class, 'update'])->name('system-config.update');
        });
    });
});

require __DIR__.'/auth.php';

// Public Shareable Read-Only Document Routes (tanpa login)
Route::get('/share/{token}', [ShareController::class, 'show'])->name('share.show');
Route::post('/share/{token}/verify', [ShareController::class, 'verify'])->name('share.verify');
Route::get('/share/{token}/preview', [ShareController::class, 'preview'])->name('share.preview');
Route::get('/share/{token}/download', [ShareController::class, 'download'])->name('share.download');
