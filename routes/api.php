<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\MenusController;
use \App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TableController;

Route::get('/test', function (Request $request) {
    $user = \App\Models\User::all();
    return response()->json([
        'data' => $user,
        'message' => 'API is working',
        'status' => 'success'
    ]);
})->name('api.test');

Route::get('/auth/google/redirect', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::middleware(['guest:api'])->group(function () {
    Route::post('/register-api', [AuthController::class, 'register']);
    Route::post('/login-api', [AuthController::class, 'login'])->name('login');
    Route::post('/verify-email', [AuthController::class, 'emailVerified']);
    Route::post('/verify-email-notification', [AuthController::class, 'emailVerificationNotification']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
    Route::get('/reset-password/{token}', function (Request $request, $token) {
        return redirect(config('app.frontend_url') . '/reset-password?token=' . $token. '&email=' . urlencode($request->email));
    })->name('password.reset');
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('get.user');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::middleware(['verified'])->group(function () {
        Route::get('/menus', [MenusController::class, 'index'])->name('menus.index');
        Route::get('/tables-available', [ReservationController::class, 'getTablesAvailable'])->name('get.tables.available');

        Route::prefix('tables')->group(function () {
            Route::get('/', [TableController::class, 'getTables'])->name('get.tables');
            Route::post('/', [TableController::class, 'upSertTable'])->name('tables.store');
            Route::delete('/delete', [TableController::class, 'deleteTable'])->name('tables.delete');
            Route::get('/generate-number', [TableController::class, 'generateTableNumber'])->name('generate.tables.number');
        });

        Route::prefix('reservation')->group(function () {
            Route::get('/', [ReservationController::class, 'getReservation'])->name('reservation.index');
            Route::get('/status', [ReservationController::class, 'getTotalStatusReservation'])->name('reservation.status');
            Route::get('/customer', [ReservationController::class, 'getReservationCustomer'])->name('reservation.customer');
            Route::post('/', [ReservationController::class, 'upSertReservation'])->name('reservation.store');
            Route::post('/update-status', [ReservationController::class, 'updateStatusReservation'])->name('reservation.updateStatus');
            Route::delete('/delete', [ReservationController::class, 'deleteReservation'])->name('reservation.delete');
        });

    });
});

