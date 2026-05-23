<?php

use App\Http\Controllers\Logistic\LoadingPrintController;
use App\Http\Controllers\Logistic\TemporaryWarehousePrintController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Pages\Dashboard\Index as DashboardIndex;
use App\Livewire\Pages\Fgd\Index as FgdIndex;
use App\Livewire\Pages\Loading\Index as LoadingIndex;
use App\Livewire\Pages\ScanLog\Index as ScanLogIndex;
use App\Livewire\Pages\Menus\Index as MenuIndex;
use App\Livewire\Pages\Roles\Index as RoleIndex;
use App\Livewire\Pages\TemporaryWarehouse\Index as TemporaryWarehouseIndex;
use App\Livewire\Pages\TemporaryWarehouseQc\Index as TemporaryWarehouseQcIndex;
use App\Livewire\Pages\Users\Index as UserIndex;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// default to login
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

     Route::get('/dashboard', DashboardIndex::class)
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    Route::get('/users', UserIndex::class)
        ->name('users.index')
        ->middleware('permission:users.view');

    Route::get('/roles', RoleIndex::class)
        ->name('roles.index')
        ->middleware('permission:roles.view');

    Route::get('/menus', MenuIndex::class)
        ->name('menus.index')
        ->middleware('permission:menus.view');

    Route::get('/temporary-warehouse', TemporaryWarehouseIndex::class)
        ->name('temporary-warehouse.index')
        ->middleware('permission:temporary-warehouse.view');

    Route::get('/temporary-warehouse-qc', TemporaryWarehouseQcIndex::class)
        ->name('temporary-warehouse-qc.index')
        ->middleware('permission:temporary-warehouse-qc.view');

    Route::get('/temporary-warehouse/print-labels', [TemporaryWarehousePrintController::class, 'labels'])
        ->name('temporary-warehouse.print-labels')
        ->middleware('permission:temporary-warehouse.view');

    Route::get('/temporary-warehouse/print-trolley-qr', [TemporaryWarehousePrintController::class, 'trolleyQr'])
        ->name('temporary-warehouse.print-trolley-qr')
        ->middleware('permission:temporary-warehouse.view');


    Route::get('/fgd', FgdIndex::class)
        ->name('fgd.index')
        ->middleware('permission:fgd.view');

    Route::get('/loading', LoadingIndex::class)
        ->name('loading.index')
        ->middleware('permission:loading.view');

      Route::get('/loading/print-do/{deliveryOrderId}', [LoadingPrintController::class, 'deliveryOrder'])
        ->name('loading.print-do')
        ->middleware('permission:loading.view');

    Route::get('/loading/print-surat-jalan/{deliveryOrderId}', [LoadingPrintController::class, 'suratJalan'])
        ->name('loading.print-surat-jalan')
        ->middleware('permission:loading.view');

    Route::get('/scan-log', ScanLogIndex::class)
        ->name('scan-log.index')
        ->middleware('permission:scan-log.view');




});

require __DIR__.'/auth.php';
