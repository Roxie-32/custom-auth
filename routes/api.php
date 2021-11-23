<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::post('/register', [App\Http\Controllers\RegisterController::class, 'register']);
Route::post('/login', [App\Http\Controllers\LoginController::class, 'login']);

Route::post('/resend/email/token', [App\Http\Controllers\RegisterController::class, 'resendPin']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('email/verify',[App\Http\Controllers\RegisterController::class, 'verifyEmail']);
    Route::middleware('verify.api')->group(function () {
        Route::post('/logout', [App\Http\Controllers\LoginController::class, 'logout']);
    });
});

Route::post('/forgot-password', [App\Http\Controllers\ForgotPasswordController::class, 'forgotPassword']);
Route::post('/verify/pin', [App\Http\Controllers\ForgotPasswordController::class, 'verifyPin']);
Route::post('/reset-password', [App\Http\Controllers\ResetPasswordController::class, 'resetPassword']);