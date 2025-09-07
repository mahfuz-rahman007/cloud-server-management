<?php

use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Server management routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Bulk operations
    Route::delete('servers/bulk-destroy', [ServerController::class, 'bulkDestroy'])->name('servers.bulk-destroy');
    Route::patch('servers/bulk-update-status', [ServerController::class, 'bulkUpdateStatus'])->name('servers.bulk-update-status');

    Route::resource('servers', ServerController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
