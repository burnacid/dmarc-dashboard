<?php

namespace App\Services\Dmarc;

use App\Models\DmarcAlertRule;
use App\Models\DmarcRecord;
use Carbon\Carbon;

class SpfFailRateSpikeAlertService
{
    /**
     * @return array<string, mixed>|null
     */
    public function evaluate(DmarcAlertRule $rule, ?Carbon $now = null): ?array
    {
        $now ??= now();

        $windowEnd = $now->copy();
        $windowStart = $windowEnd->copy()->subMinutes((int) $rule->window_minutes);
        $baselineEnd = $windowStart->copy();
        $baselineStart = $baselineEnd->copy()->subDays((int) $rule->baseline_days);

        $current = $this->aggregatePeriod($rule, $windowStart, $windowEnd);
        $baseline = $this->aggregatePeriod($rule, $baselineStart, $baselineEnd);

        if ($current['total_messages'] < (int) $rule->min_messages || $baseline['total_messages'] === 0) {
            return null;
        }

        $currentRate = $this->failRatePercent($current['spf_fail_messages'], $current['total_messages']);
        $baselineRate = $this->failRatePercent($baseline['spf_fail_messages'], $baseline['total_messages']);
        $absoluteIncrease = $currentRate - $baselineRate;

        $ratioThresholdMet = $baselineRate > 0
            ? $currentRate >= ($baselineRate * (float) $rule->threshold_multiplier)
            : $currentRate >= (float) $rule->min_absolute_increase;

        if (! $ratioThresholdMet || $absoluteIncrease < (float) $rule->min_absolute_increase) {
            return null;
        }

        return [
            'rule_id' => $rule->id,
            'metric' => $rule->metric,
            'domain' => $rule->domain,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'baseline_start' => $baselineStart,
            'baseline_end' => $baselineEnd,
            'current_total_messages' => $current['total_messages'],
            'current_spf_fail_messages' => $current['spf_fail_messages'],
            'current_fail_rate' => $currentRate,
            'baseline_total_messages' => $baseline['total_messages'],
            'baseline_spf_fail_messages' => $baseline['spf_fail_messages'],
            'baseline_fail_rate' => $baselineRate,
            'absolute_increase' => $absoluteIncrease,
        ];
    }

    /**
     * @return array{total_messages:int,spf_fail_messages:int}
     */
    private function aggregatePeriod(DmarcAlertRule $rule, Carbon $start, Carbon $end): array
    {
        $totals = DmarcRecord::query()
            ->join('dmarc_reports', 'dmarc_reports.id', '=', 'dmarc_records.dmarc_report_id')
            ->join('imap_accounts', 'imap_accounts.id', '=', 'dmarc_reports.imap_account_id')
            ->where('imap_accounts.user_id', $rule->user_id)
            ->whereRaw('COALESCE(dmarc_reports.report_end_at, dmarc_reports.created_at) BETWEEN ? AND ?', [$start, $end])
            ->when(
                filled($rule->domain),
                fn ($query) => $query->where(function ($domainQuery) use ($rule): void {
                    $domainQuery
                        ->where('dmarc_records.header_from', $rule->domain)
                        ->orWhere(function ($fallbackQuery) use ($rule): void {
                            $fallbackQuery
                                ->where(function ($emptyHeader): void {
                                    $emptyHeader
                                        ->whereNull('dmarc_records.header_from')
                                        ->orWhere('dmarc_records.header_from', '');
                                })
                                ->where('dmarc_reports.policy_domain', $rule->domain);
                        });
                })
            )
            ->selectRaw('COALESCE(SUM(dmarc_records.message_count), 0) as total_messages')
            ->selectRaw("COALESCE(SUM(CASE WHEN LOWER(dmarc_records.spf) = 'fail' THEN dmarc_records.message_count ELSE 0 END), 0) as spf_fail_messages")
            ->first();

        return [
            'total_messages' => (int) ($totals?->total_messages ?? 0),
            'spf_fail_messages' => (int) ($totals?->spf_fail_messages ?? 0),
        ];
    }

    private function failRatePercent(int $failMessages, int $totalMessages): float
    {
        if ($totalMessages <= 0) {
            return 0.0;
        }

        return round(($failMessages / $totalMessages) * 100, 2);
    }
}
