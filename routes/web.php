<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductionReportController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\Master\ProductController;

// Rute Tamu (Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Rute Terautentikasi (Sudah Login: Admin & Team Leader)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Khusus Admin: Cetak BSTB & Konsolidasi Shift
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports/bstb-shift', [ProductionReportController::class, 'bstbShift'])->name('reports.bstb-shift');
        Route::get('/reports/{id}/bstb', [ProductionReportController::class, 'bstb'])->name('reports.bstb');
        Route::post('/reports/{id}/bstb-meta', [ProductionReportController::class, 'updateBstbMeta'])->name('reports.bstb-meta');
    });

    // Laporan Produksi (Akses Bersama: Admin & Team Leader)
    Route::resource('reports', ProductionReportController::class);

    // Khusus Admin: Manajemen Batch dan Standar Manufaktur & Mesin
    Route::middleware('role:admin')->group(function () {
        Route::resource('batches', BatchController::class);
        Route::get('/master/products', [ProductController::class, 'index'])->name('master.products');
    });
});

