<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DmarcReportController;
use App\Http\Controllers\ImapAccountController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/poll-now', [DashboardController::class, 'pollNow'])->name('dashboard.poll-now');
    Route::get('/reports', [DmarcReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{dmarcReport}', [DmarcReportController::class, 'show'])->name('reports.show');
    Route::resource('imap-accounts', ImapAccountController::class)->except(['show']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
