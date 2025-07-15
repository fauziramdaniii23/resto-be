<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\MenusController;
use \App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\ReservationController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/auth/google/redirect', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::middleware(['guest:api'])->group(function () {
    Route::post('/register-api', [AuthController::class, 'register']);
    Route::post('/login-api', [AuthController::class, 'login'])->name('login');
    Route::post('/verify-email', [AuthController::class, 'emailVerified']);
    Route::post('/verify-email-notification', [AuthController::class, 'emailVerificationNotification']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    Route::get('/reset-password/{token}', function (Request $request, $token) {
        return redirect(config('app.frontend_url') . '/reset-password?token=' . $token. '&email=' . urlencode($request->email));
    })->name('password.reset');
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('get.user');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::middleware(['verified'])->group(function () {
        Route::get('/menus', [MenusController::class, 'index'])->name('menus.index');
        Route::get('/tables', [ReservationController::class, 'getTablesAvailable'])->name('get.tables');
        Route::post('/reservation', [ReservationController::class, 'addReservation'])->name('add.reservation');
        Route::get('/reservation', [ReservationController::class, 'getReservation'])->name('get.reservation');
    });
});

