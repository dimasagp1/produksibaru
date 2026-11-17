<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductionReportController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\Master\ProductController;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Production Reports (Index, Create, Store, Show, BSTB Print Single & Konsolidasi Shift)
Route::get('/reports/bstb-shift', [ProductionReportController::class, 'bstbShift'])->name('reports.bstb-shift');
Route::get('/reports/{id}/bstb', [ProductionReportController::class, 'bstb'])->name('reports.bstb');
Route::post('/reports/{id}/bstb-meta', [ProductionReportController::class, 'updateBstbMeta'])->name('reports.bstb-meta');
Route::resource('reports', ProductionReportController::class);

// Batches Management (CRUD, Show Pipeline, Status Toggle)
Route::resource('batches', BatchController::class);

// Master Data Standards
Route::get('/master/products', [ProductController::class, 'index'])->name('master.products');

// Logout Route
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/dashboard');
})->name('logout');
