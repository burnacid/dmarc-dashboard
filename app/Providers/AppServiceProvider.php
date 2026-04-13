<?php

namespace App\Providers;

use App\Services\Dns\NativeTxtRecordResolver;
use App\Services\Dns\TxtRecordResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
