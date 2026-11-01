<?php

use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'actif'])->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');

    // Espace réservé à l'Administrateur
    Route::middleware('role:administrateur')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('utilisateurs', UserController::class)
            ->parameters(['utilisateurs' => 'user'])
            ->except(['show', 'destroy']);
        Route::patch('utilisateurs/{user}/statut', [UserController::class, 'basculerStatut'])
            ->name('utilisateurs.statut');
    });
});

require __DIR__.'/auth.php';