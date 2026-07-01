<?php

use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\AuthDiagnosticLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DmarcReportController;
use App\Http\Controllers\DomainFilterController;
use App\Http\Controllers\ImapAccountController;
use App\Http\Controllers\OrganizationFilterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportSettingsController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SystemLogController;
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
    Route::post('/filters/organization', [OrganizationFilterController::class, 'update'])->name('filters.organization.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/poll-now', [DashboardController::class, 'pollNow'])->name('dashboard.poll-now');
    Route::get('/dashboard/live-stats', [DashboardController::class, 'liveStats'])->name('dashboard.live-stats');
    Route::get('/reports', [DmarcReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{dmarcReport}', [DmarcReportController::class, 'show'])->name('reports.show');
    Route::resource('imap-accounts', ImapAccountController::class)->except(['show']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/report-settings', [ReportSettingsController::class, 'update'])->name('profile.report-settings.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/rules', [AlertController::class, 'storeRule'])->name('alerts.rules.store');
    Route::patch('/alerts/rules/{dmarcAlertRule}', [AlertController::class, 'updateRule'])->name('alerts.rules.update');
    Route::delete('/alerts/rules/{dmarcAlertRule}', [AlertController::class, 'destroyRule'])->name('alerts.rules.destroy');
    Route::get('/alerts/channels', [AlertController::class, 'channels'])->name('alerts.channels');
    Route::post('/alerts/channels', [AlertController::class, 'storeChannel'])->name('alerts.channels.store');
    Route::patch('/alerts/channels/{dmarcNotificationChannel}', [AlertController::class, 'updateChannel'])->name('alerts.channels.update');
    Route::delete('/alerts/channels/{dmarcNotificationChannel}', [AlertController::class, 'destroyChannel'])->name('alerts.channels.destroy');
    Route::post('/alerts/{dmarcAlertRule}/test-fire', [AlertController::class, 'testFire'])->name('alerts.test-fire');

    Route::middleware('can:view-admin-tools')->group(function () {
        Route::get('/auth-diagnostics', [AuthDiagnosticLogController::class, 'index'])->name('auth-diagnostics.index');
        Route::get('/auth-diagnostics/{authDiagnosticLog}', [AuthDiagnosticLogController::class, 'show'])->name('auth-diagnostics.show');
        Route::delete('/auth-diagnostics', [AuthDiagnosticLogController::class, 'destroy'])->name('auth-diagnostics.destroy');

        Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');
        Route::get('/system-logs/{systemLog}', [SystemLogController::class, 'show'])->name('system-logs.show');
        Route::delete('/system-logs', [SystemLogController::class, 'destroy'])->name('system-logs.destroy');
    });

    Route::middleware('can:manage-users')->prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('can:manage-roles')->prefix('admin/roles')->name('admin.roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::patch('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('can:manage-organizations')->prefix('admin/organizations')->name('admin.organizations.')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');
        Route::get('/create', [OrganizationController::class, 'create'])->name('create');
        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}/edit', [OrganizationController::class, 'edit'])->name('edit');
        Route::patch('/{organization}', [OrganizationController::class, 'update'])->name('update');
        Route::delete('/{organization}', [OrganizationController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('can:manage-domains')->prefix('admin/domains')->name('admin.domains.')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('index');
        Route::post('/', [DomainController::class, 'store'])->name('store');
        Route::patch('/{domain}', [DomainController::class, 'update'])->name('update');
        Route::delete('/{domain}', [DomainController::class, 'destroy'])->name('destroy');
    });

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
