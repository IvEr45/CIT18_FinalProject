<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/chat', function () {
    return view('chat'); // This serves the chat page
});

Route::post('/chat', [GeminiController::class, 'chat']);

Route::post('/save-recipe', [GeminiController::class, 'store']);
Route::get('/recipes', [GeminiController::class, 'index']);
Route::delete('/recipes/{id}', [GeminiController::class, 'destroy']);
Route::put('/recipes/{id}', [GeminiController::class, 'update']);
