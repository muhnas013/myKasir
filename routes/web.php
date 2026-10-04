<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PinLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pos\OfflineSyncController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Menu\Index as MenuIndex;
use App\Livewire\Payroll\Index as PayrollIndex;
use App\Livewire\Pos\History as PosHistory;
use App\Livewire\Pos\Register as PosRegister;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Shift\Close as ShiftClose;
use App\Livewire\Shift\Open as ShiftOpen;
use App\Livewire\Stock\Index as StockIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::get('/login/pin', [PinLoginController::class, 'show'])->name('login.pin');
Route::post('/login/pin', [PinLoginController::class, 'store'])->name('login.pin.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/lock', [PinLoginController::class, 'lock'])->name('lock');

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/shift/open', ShiftOpen::class)->middleware('can:pos.transact')->name('shift.open');
    Route::get('/shift/close', ShiftClose::class)->middleware(['can:pos.transact', 'shift.open'])->name('shift.close');

    Route::get('/pos', PosRegister::class)->middleware(['can:pos.transact', 'shift.open'])->name('pos.index');
    // Bukan 'shift.open': dipanggil fetch() offline-sync JS tanpa Livewire; shift divalidasi di dalam Action (lihat 06 P7).
    Route::post('/pos/offline-sync', [OfflineSyncController::class, 'store'])->middleware('can:pos.transact')->name('pos.offline-sync');
    Route::get('/orders', PosHistory::class)->middleware('can:pos.transact')->name('orders.index');
    Route::get('/receipts/{order}', [ReceiptController::class, 'show'])->name('receipts.show');

    Route::get('/menu', MenuIndex::class)
        ->middleware('can:menu.manage')
        ->name('menu.index');

    Route::get('/stock', StockIndex::class)
        ->middleware('can:stock.manage')
        ->name('stock.index');

    Route::get('/reports', ReportsIndex::class)
        ->middleware('can:report.view-own')
        ->name('reports.index');

    Route::get('/reports/export', ReportExportController::class)
        ->middleware('can:report.view-all')
        ->name('reports.export');

    Route::get('/settings', SettingsIndex::class)
        ->middleware('can:settings.manage')
        ->name('settings.index');

    Route::get('/payroll', PayrollIndex::class)
        ->middleware('can:settings.manage')
        ->name('payroll.index');
});
