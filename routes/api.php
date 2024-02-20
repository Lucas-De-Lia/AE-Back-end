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

    //Route::post('/email/verify/{id}/{hash}', [AuthController::class, 'email_verify']);
    //Route::post('/email/verification-notification', [AuthController::class, 'verification_notification']);

    Route::post('/email/verify/send', [AuthController::class, 'email_send_code']);
    Route::get('/email/verify-link', [AuthController::class, 'verify_link_email']);
    Route::post('/email/verify', [AuthController::class, 'verify_code_email']);

    Route::post('/forgot-password', [AuthController::class, 'forgot_password']);
    Route::post('/reset-password', [AuthController::class, 'reset_password']);
    Route::post('/change_password', [AuthController::class, 'change_password']);
});

Route::prefix('ae')->group(function () {
    Route::get('aedates', [AeController::class, 'get_calendar_dates']);
    Route::get('fetch-start-pdf', [AeController::class, 'fetch_start_pdf']);
    Route::get('fetch-end-pdf', [AeController::class, 'fetch_end_pdf']);
    Route::get('fetch-user-data', [AeController::class, 'fetch_user_data']);
    //Route::get('ae-dates', [AeController::class, 'getCalendarDates']);
    Route::post('start_n', [AeController::class, 'start_ae_n']);
    Route::post('finalize', [AeController::class, 'finalize_ae']);
});

Route::prefix('resources')->group(function () {
    Route::post('upload-pdf-document', [GuestController::class, 'uploadPdfDocument']);
    //Route::post('getpdf', [GuestController::class, 'getPdf']);

    //Route::get('getpdflist', [GuestController::class, 'getPdfList']);
    Route::get('getQuestions', [GuestController::class, 'getQuestionList']);
});
