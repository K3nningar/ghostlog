<?php

use App\Http\Controllers\Admin\ItemAdminController;
use App\Http\Controllers\GhostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemLocaleController;
use App\Http\Controllers\EmblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\SparrowController;
use App\Http\Controllers\WeaponController;
use App\Http\Controllers\ArmorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sparrows', [SparrowController::class, 'index'])->name('sparrows.index');

Route::get('/ships', [ShipController::class, 'index'])->name('ships.index');

Route::get('/emblems', [EmblemController::class, 'index'])->name('emblems.index');

Route::get('/ghosts', [GhostController::class, 'index'])->name('ghosts.index');

Route::get('/weapons', [WeaponController::class, 'index'])->name('weapons.index');
Route::get('/weapons/{hash}/perks', [WeaponController::class, 'perks'])->name('weapons.perks');


Route::get('/armors', [ArmorController::class, 'index'])->name('armors.index');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
 * Interface d'administration : auth requise + ACL admin (middleware `admin`).
 * Toute tentative d'un visiteur ou d'un utilisateur non-admin → 403.
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/items', [ItemAdminController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemAdminController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemAdminController::class, 'store'])->name('items.store');
    // Binding explicite par id : le modèle Item est lié par `hash` par défaut,
    // mais l'admin cible précisément UNE traduction via sa clé primaire.
    Route::get('/items/{item:id}/edit', [ItemAdminController::class, 'edit'])->name('items.edit');
    Route::put('/items/{item:id}', [ItemAdminController::class, 'update'])->name('items.update');
});

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale', 'fr');

    $request->session()->put('locale', $locale);

    return back();
})->name('locale.update');

Route::get('/items/{hash}/locales', [ItemLocaleController::class, 'index'])
    ->name('items.locales');

require __DIR__.'/auth.php';
