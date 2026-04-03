<?php

use App\Http\Controllers\Api\PresenceController;
use Illuminate\Support\Facades\Route;

Route::post('/presensi', [PresenceController::class, 'store']);
Route::get('/get-date', [PresenceController::class, 'getDateTime']);
Route::get('/user/image', [PresenceController::class, 'getUserImage']);
Route::get('/status', function() {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});
