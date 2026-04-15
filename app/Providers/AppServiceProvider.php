<?php

namespace App\Providers;

use App\Services\Dns\NativeTxtRecordResolver;
use App\Services\Dns\TxtRecordResolver;
use App\Support\Auth\AuthDiagnostics;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TxtRecordResolver::class, NativeTxtRecordResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Attempting::class, function (Attempting $event): void {
            if (! app()->bound('request')) {
                return;
            }

            AuthDiagnostics::log('event.attempting', request(), [
                'guard' => $event->guard,
                'remember_requested' => $event->remember,
                'credential_keys' => array_values(array_diff(array_keys($event->credentials), ['password'])),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            if (! app()->bound('request')) {
                return;
            }

            AuthDiagnostics::log('event.failed', request(), [
                'guard' => $event->guard,
                'user_id' => $event->user?->getAuthIdentifier(),
                'credential_keys' => array_values(array_diff(array_keys($event->credentials), ['password'])),
            ], 'warning');
        });

        Event::listen(Login::class, function (Login $event): void {
            if (! app()->bound('request')) {
                return;
            }

            AuthDiagnostics::log('event.login', request(), [
                'guard' => $event->guard,
                'user_id' => $event->user->getAuthIdentifier(),
                'remember_effective' => $event->remember,
            ]);
        });

        Event::listen(Authenticated::class, function (Authenticated $event): void {
            if (! app()->bound('request')) {
                return;
            }

            AuthDiagnostics::log('event.authenticated', request(), [
                'guard' => $event->guard,
                'user_id' => $event->user->getAuthIdentifier(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! app()->bound('request')) {
                return;
            }

            AuthDiagnostics::log('event.logout', request(), [
                'guard' => $event->guard,
                'user_id' => $event->user?->getAuthIdentifier(),
            ]);
        });

        View::composer('layouts.navigation', function ($view): void {
            $user = Auth::user();

            if (! $user) {
                $view->with('globalDomainOptions', collect())
                    ->with('globalSelectedDomain', '');

                return;
            }

            $domainOptions = DB::table('dmarc_records')
                ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
                ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
                ->where('imap_accounts.user_id', $user->id)
                ->selectRaw("COALESCE(NULLIF(dmarc_records.header_from, ''), dmarc_reports.policy_domain) as domain")
                ->whereNotNull(DB::raw("COALESCE(NULLIF(dmarc_records.header_from, ''), dmarc_reports.policy_domain)"))
                ->distinct()
                ->orderBy('domain')
                ->pluck('domain')
                ->filter(fn ($domain) => is_string($domain) && trim($domain) !== '')
                ->values();

            $selectedDomain = session('filters.domain', '');

            if (! is_string($selectedDomain) || ! $domainOptions->contains($selectedDomain)) {
                $selectedDomain = '';
            }

            $view->with('globalDomainOptions', $domainOptions)
                ->with('globalSelectedDomain', $selectedDomain);
        });
    }
}
