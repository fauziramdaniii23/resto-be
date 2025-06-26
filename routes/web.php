<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
Route::post('/login', [AuthController::class, 'login']);

//require __DIR__.'/auth.php';
