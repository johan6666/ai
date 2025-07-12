<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chat', [ChatController::class, 'index']);
Route::post('/chat', [ChatController::class, 'chat']);

// --- RUTE BARU UNTUK REKOMENDASI ---
Route::get('/recommendation', [ChatController::class, 'recommendationIndex']);
Route::post('/generate-recommendation', [ChatController::class, 'generateRecommendation']);
