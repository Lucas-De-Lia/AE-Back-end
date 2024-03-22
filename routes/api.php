<?php

use App\Http\Controllers\API\AeController;
use App\Http\Controllers\API\PasswordsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GuestController;
use App\Http\Controllers\API\EmailVerifyController;



Route::prefix('auth')->group(function () {
    Route::post('photos', [AuthController::class, 'merge_dni_photos']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

Route::prefix('password')->group(function () {
    Route::get('reset-password/{token}', function (string $token) {//esto es necesario para enviar el link pero no se usa
        return response()->json(['status' => false]); //esto es necesario para enviar el link pero no se usa
    })->middleware('guest')->name('password.reset'); //esto es necesario para enviar el link pero no se usa

    Route::post('forgot', [PasswordsController::class, 'forgot_password'])->name('password.email');
    Route::post('reset', [PasswordsController::class, 'reset_password'])->name('password.update');
    Route::post('change', [PasswordsController::class, 'change_password']);
});

Route::prefix('email')->group(function () {
    //rename functions
    Route::get('verify', function () {
        return response()->json(['status' => false]);
    })->middleware('auth:api')->name('verification.notice');

    Route::post('verify/{id}/{hash}', [EmailVerifyController::class, 'email_verify'])->name('verification.verify');
    Route::post('notification', [EmailVerifyController::class, 'email_send'])->name('verification.resend');
    Route::post('change', [EmailVerifyController::class, 'email_change']);
});


Route::prefix('ae')->group(function () {
    Route::get('dates', [AeController::class, 'get_calendar_dates']);
    Route::get('fetch-start-pdf', [AeController::class, 'fetch_start_pdf']);
    Route::get('fetch-end-pdf', [AeController::class, 'fetch_end_pdf']);
    Route::get('fetch-user-data', [AeController::class, 'fetch_user_data']);
    //Route::get('ae-dates', [AeController::class, 'getCalendarDates']);
    Route::post('start-n', [AeController::class, 'start_ae_n']);
    Route::post('finalize', [AeController::class, 'finalize_ae']);
});

Route::prefix('resources')->group(function () {
    Route::post('upload-pdf-document', [GuestController::class, 'uploadPdfDocument']);
    Route::post('get-news-list', [GuestController::class, 'getNewsList']);
    Route::post('get-news-pdf', [GuestController::class, 'getNewsPdf']);

    Route::get('getQuestions', [GuestController::class, 'getQuestionList']);
});
