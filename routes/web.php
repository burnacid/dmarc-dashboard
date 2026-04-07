<?php

use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DmarcReportController;
use App\Http\Controllers\DomainFilterController;
use App\Http\Controllers\ImapAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportSettingsController;
use App\Http\Controllers\SecurityController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;

// Home route: redirect to dashboard for authenticated users, or to login for guests
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');

Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.challenge.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/filters/domain', [DomainFilterController::class, 'update'])->name('filters.domain.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/poll-now', [DashboardController::class, 'pollNow'])->name('dashboard.poll-now');
    Route::get('/reports', [DmarcReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{dmarcReport}', [DmarcReportController::class, 'show'])->name('reports.show');
    Route::resource('imap-accounts', ImapAccountController::class)->except(['show']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/report-settings', [ReportSettingsController::class, 'update'])->name('profile.report-settings.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('security')->name('security.')->group(function () {
        Route::post('/two-factor', [SecurityController::class, 'storeTwoFactor'])->name('two-factor.store');
        Route::post('/two-factor/confirm', [SecurityController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
        Route::delete('/two-factor', [SecurityController::class, 'destroyTwoFactor'])->name('two-factor.destroy');
        Route::post('/recovery-codes', [SecurityController::class, 'storeRecoveryCodes'])->name('recovery-codes.store');
        Route::delete('/passkeys/{credential}', [SecurityController::class, 'destroyPasskey'])->name('passkeys.destroy');
    });
});

WebAuthnRoutes::register()->withoutMiddleware(ValidateCsrfToken::class);

require __DIR__.'/auth.php';
