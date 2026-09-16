<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemLocaleController;
use App\Http\Controllers\EmblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\SparrowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sparrows', [SparrowController::class, 'index'])->name('sparrows.index');
Route::get('/ships', [ShipController::class, 'index'])->name('ships.index');
Route::get('/emblems', [EmblemController::class, 'index'])->name('emblems.index');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale', 'fr');

    $request->session()->put('locale', $locale);

    return back();
})->name('locale.update');

Route::get('/items/{hash}/locales', [ItemLocaleController::class, 'index'])
    ->name('items.locales');

require __DIR__.'/auth.php';
