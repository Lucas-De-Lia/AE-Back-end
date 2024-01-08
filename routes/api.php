<?php

use App\Http\Controllers\API\AeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GuestController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('/email/verify/{id}/{hash}', [AuthController::class, 'email_verify']);
    Route::post('/email/verification-notification', [AuthController::class, 'verification_notification']);

    Route::post('/email/verify/send', [AuthController::class, 'email_send_code']);
    Route::post('/email/verify', [AuthController::class, 'verify_code_email']);

    Route::post('/forgot-password', [AuthController::class, 'forgot_password']);
    Route::post('/reset-password', [AuthController::class, 'reset_password']);
    Route::post('/change_password', [AuthController::class, 'change_password']);
});

Route::prefix('ae')->group(function () {
    Route::post('aedates', [AeController::class, 'getaedates']);
    Route::post('send', [AeController::class, 'send']);
});

Route::prefix('resources')->group(function () {
    Route::post('getpdf', [GuestController::class, 'getPdf']);
    Route::post('getpdflist', [GuestController::class, 'getPdfList']);
    Route::post('publishpdf', [GuestController::class, 'publishPDF']);

    Route::post('getQuestions', [GuestController::class, 'getQuestionList']);
});
