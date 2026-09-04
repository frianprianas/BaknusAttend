<?php

use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\SelfieAttendanceController;
use Illuminate\Support\Facades\Route;

// --- Public Auth Routes (Flutter) ---
Route::prefix('auth')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login']);
});

// --- Protected Presence & User Routes (Flutter Bearer Token) ---
Route::middleware('api.token')->group(function () {
    Route::get('/auth/me', [ApiAuthController::class, 'me']);

    Route::prefix('presence')->group(function () {
        Route::get('/status', [SelfieAttendanceController::class, 'getTodayStatus']);
        Route::post('/selfie', [SelfieAttendanceController::class, 'submitSelfie']);
        Route::post('/register-face', [SelfieAttendanceController::class, 'registerMasterFace']);
    });
});

// --- Existing System Endpoints ---
Route::post('/presensi', [PresenceController::class, 'store']);
Route::get('/get-date', [PresenceController::class, 'getDateTime']);
Route::get('/user/image', [PresenceController::class, 'getUserImage']);
Route::get('/dashboard-stats', [PresenceController::class, 'getDashboardStats']);
Route::get('/user-stats', [PresenceController::class, 'getUserStats']);
Route::get('/presence/unattended-emails', [PresenceController::class, 'getUnattendedEmails']);

// Endpoint untuk Tap Kartu RFID (Mesin RFID / MPS1)
Route::get('/attendance/tap', [AttendanceController::class, 'tap']);
Route::post('/attendance/tap', [AttendanceController::class, 'tap']);

Route::get('/status', function() {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});

