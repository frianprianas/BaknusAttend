<?php

use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::post('/presensi', [PresenceController::class, 'store']);
Route::get('/get-date', [PresenceController::class, 'getDateTime']);
Route::get('/user/image', [PresenceController::class, 'getUserImage']);

// Endpoint untuk Tap Kartu RFID (Mesin RFID / MPS1)
Route::get('/attendance/tap', [AttendanceController::class, 'tap']);
Route::post('/attendance/tap', [AttendanceController::class, 'tap']);

Route::get('/status', function() {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});
