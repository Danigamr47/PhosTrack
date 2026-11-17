<?php

use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\TypeMateriauController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CarteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraglineController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HistoriqueController;
use App\Http\Controllers\LeveController;
use App\Http\Controllers\LevePointsController;
use App\Http\Controllers\PositionDraglineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'actif'])->group(function () {

    // ── Pages accessibles à tous les rôles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/carte', [CarteController::class, 'index'])->name('carte');
    Route::get('/carte/donnees', [CarteController::class, 'donnees'])->name('carte.donnees');
    Route::get('/carte/passes', [CarteController::class, 'passes'])->name('carte.passes');

    Route::get('/historique', [HistoriqueController::class, 'index'])->name('historique.index');

    Route::get('draglines', [DraglineController::class, 'index'])->name('draglines.index');

    // Export des coordonnées
    Route::get('leves/{leve}/export/{type}', ExportController::class)
        ->name('leves.export')
        ->where('type', 'pied|semis|tous|contour');

    // Profil : modification du mot de passe uniquement
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    //Technicien uniquement
    Route::middleware('role:technicien')->group(function () {
        Route::post('draglines/{dragline}/positions', [PositionDraglineController::class, 'store'])
            ->name('draglines.positions.store');
        Route::post('draglines/{dragline}/positions/import', [PositionDraglineController::class, 'import'])
            ->name('draglines.positions.import');
        Route::delete('positions-draglines/{position}', [PositionDraglineController::class, 'destroy'])
            ->name('draglines.positions.destroy');

        // Doit rester AVANT leves
        Route::resource('leves', LeveController::class)
            ->parameters(['leves' => 'leve'])
            ->except(['index', 'show']);

        Route::post('leves/{leve}/points/{type}', [LevePointsController::class, 'store'])
            ->name('leves.points.store')->where('type', 'pied|semis');
        Route::delete('leves/{leve}/points/{type}', [LevePointsController::class, 'destroy'])
            ->name('leves.points.destroy')->where('type', 'pied|semis');
        Route::post('leves/{leve}/calcul', [LeveController::class, 'calculer'])->name('leves.calcul');
        Route::post('leves/{leve}/validation', [LeveController::class, 'valider'])->name('leves.valider');

        Route::resource('stocks', StockController::class)->except(['index', 'show']);
    });

    // Administrateur et Technicien
    Route::middleware('role:administrateur,technicien')->group(function () {
        Route::resource('leves', LeveController::class)
            ->parameters(['leves' => 'leve'])
            ->only(['index', 'show']);
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    });

    // Administrateur uniquement
    Route::middleware('role:administrateur')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('utilisateurs', UserController::class)
            ->parameters(['utilisateurs' => 'user'])
            ->except(['show', 'destroy']);
        Route::patch('utilisateurs/{user}/statut', [UserController::class, 'basculerStatut'])
            ->name('utilisateurs.statut');

        Route::resource('types-materiau', TypeMateriauController::class)
            ->parameters(['types-materiau' => 'type'])
            ->except(['show', 'create']);
            
        Route::post('draglines', [DraglineController::class, 'store'])->name('draglines.store');
        Route::put('draglines/{dragline}', [DraglineController::class, 'update'])->name('draglines.update');

        Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
    });
});

require __DIR__.'/auth.php';