<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Redirect root ke admin
Route::get('/', function () {
    return redirect('/admin');
});

// Route login mandiri (tanpa Livewire/Filament form)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// API Endpoint untuk Mesin RFID (tanpa CSRF via bootstrap/app.php)
Route::match(['get', 'post'], '/api/attendance/tap', [AttendanceController::class, 'tap']);

// Endpoint untuk PWA Push Subscription
Route::post('/push/subscribe', [\App\Http\Controllers\PushNotificationController::class, 'store']);

// Route Cetak & Export Rekap Kehadiran Guru/TU Khusus Admin
Route::get('/admin/rekap-guru-tu/print', [\App\Http\Controllers\LaporanGuruTuController::class, 'print'])->name('admin.rekap-guru-tu.print');
Route::get('/admin/rekap-guru-tu/export-csv', [\App\Http\Controllers\LaporanGuruTuController::class, 'exportCsv'])->name('admin.rekap-guru-tu.export-csv');
