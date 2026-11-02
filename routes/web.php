<?php

use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TypeMateriauController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'actif'])->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    // Consultation : tous les rôles
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    // Gestion : Technicien uniquement
    Route::middleware('role:technicien')->group(function () {
        Route::resource('stocks', StockController::class)->except(['index', 'show']);
    });
    // Espace réservé à l'Administrateur
    Route::middleware('role:administrateur')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('utilisateurs', UserController::class)
            ->parameters(['utilisateurs' => 'user'])
            ->except(['show', 'destroy']);
        Route::patch('utilisateurs/{user}/statut', [UserController::class, 'basculerStatut'])
            ->name('utilisateurs.statut');
        
        Route::resource('types-materiau', TypeMateriauController::class)
            ->parameters(['types-materiau' => 'type'])
            ->except(['show', 'create']);
    });
});

require __DIR__.'/auth.php';