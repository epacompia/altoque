<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider by default, which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Rutas para autenticación con Google (API)
Route::get('/api/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/api/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

// require __DIR__.'/auth.php';