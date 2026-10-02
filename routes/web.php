<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportNilaiController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SupervisiController;
use Illuminate\Support\Facades\Route;

// Rute dan teks antarmuka sengaja berbahasa Indonesia (pengguna orang Indonesia).

Route::redirect('/', '/dashboard');

// ---- Masuk -------------------------------------------------------------------
Route::middleware(['guest', 'no-store'])->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth');

// ---- Setelah masuk -----------------------------------------------------------
Route::middleware(['auth', 'aktif', 'no-store', 'throttle:tulis'])->group(function () {
    Route::get('/dashboard', DashboardController::class);

    Route::get('/profil', [ProfilController::class, 'edit']);
    Route::patch('/profil/password', [ProfilController::class, 'updatePassword']);

    // Nilai siswa: kelas -> siswa -> nilai.
    Route::get('/nilai', [KelasController::class, 'index']);
    Route::post('/kelas', [KelasController::class, 'store']);
    Route::patch('/kelas/{kelas}', [KelasController::class, 'update']);
    Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy']);

    Route::get('/kelas/{kelas}', [SiswaController::class, 'index']);
    Route::post('/kelas/{kelas}/siswa', [SiswaController::class, 'store']);
    Route::get('/kelas/{kelas}/siswa/{siswa}', [SiswaController::class, 'show']);
    Route::patch('/siswa/{siswa}', [SiswaController::class, 'update']);
    Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy']);
    Route::put('/siswa/{siswa}/nilai', [NilaiController::class, 'update']);

    Route::post('/nilai/impor', [ImportController::class, 'siswa'])->middleware('throttle:impor');
    Route::get('/nilai/ekspor', [ExportNilaiController::class, 'nilai'])->middleware('throttle:ekspor');
    Route::get('/nilai/format-kosong', [ExportNilaiController::class, 'templateKosong'])->middleware('throttle:ekspor');

    // Supervisi guru. `/supervisi/baru` harus sebelum `/supervisi/{supervisi}`.
    Route::get('/supervisi', [SupervisiController::class, 'index']);
    Route::get('/supervisi/baru', [SupervisiController::class, 'create']);
    Route::post('/supervisi', [SupervisiController::class, 'store']);
    Route::get('/supervisi/{supervisi}', [SupervisiController::class, 'show']);
    Route::put('/supervisi/{supervisi}', [SupervisiController::class, 'update']);
    Route::delete('/supervisi/{supervisi}', [SupervisiController::class, 'destroy']);
    Route::get('/supervisi/{supervisi}/ekspor', [SupervisiController::class, 'export'])->middleware('throttle:ekspor');

    // Kelola akun: hanya admin.
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/akun', [AkunController::class, 'index']);
        Route::post('/akun', [AkunController::class, 'store']);
        Route::patch('/akun/{user}', [AkunController::class, 'update']);
        Route::delete('/akun/{user}', [AkunController::class, 'destroy']);
    });
});
