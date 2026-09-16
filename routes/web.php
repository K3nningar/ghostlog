<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemLocaleController;
use App\Http\Controllers\EmblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\SparrowController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/sparrows', [SparrowController::class, 'index'])->name('sparrows.index');
Route::get('/ships', [ShipController::class, 'index'])->name('ships.index');
Route::get('/emblems', [EmblemController::class, 'index'])->name('emblems.index');

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale', 'fr');

    $request->session()->put('locale', $locale);

    return back();
})->name('locale.update');

Route::get('/items/{hash}/locales', [ItemLocaleController::class, 'index'])
    ->name('items.locales');

require __DIR__.'/auth.php';
