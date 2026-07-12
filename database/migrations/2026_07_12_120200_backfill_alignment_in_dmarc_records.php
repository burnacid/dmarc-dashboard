<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Pdp\Rules;

return new class extends Migration
{
    private ?Rules $rules = null;

    private bool $rulesLoadAttempted = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('dmarc_reports')
            ->select(['id', 'raw_xml'])
            ->orderBy('id')
            ->chunkById(100, function ($reports): void {
                foreach ($reports as $report) {
                    $xml = is_string($report->raw_xml) ? trim($report->raw_xml) : '';

                    if ($xml === '') {
                        continue;
                    }

                    libxml_use_internal_errors(true);
                    $root = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);

                    if (! $root instanceof \SimpleXMLElement) {
                        continue;
                    }

                    $aspf = $this->firstNodeValue($root, 'policy_published/aspf');
                    $adkim = $this->firstNodeValue($root, 'policy_published/adkim');

                    DB::table('dmarc_reports')
                        ->where('id', $report->id)
                        ->update(['aspf' => $aspf, 'adkim' => $adkim]);

                    DB::table('dmarc_records')
                        ->where('dmarc_report_id', $report->id)
                        ->orderBy('id')
                        ->get(['id', 'header_from', 'spf_domain', 'dkim_domain'])
                        ->each(function ($record) use ($aspf, $adkim): void {
                            DB::table('dmarc_records')
                                ->where('id', $record->id)
                                ->update([
                                    'spf_aligned' => $this->isAligned($record->header_from, $record->spf_domain, $aspf),
                                    'dkim_aligned' => $this->isAligned($record->header_from, $record->dkim_domain, $adkim),
                                ]);
                        });
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('dmarc_records')->update(['spf_aligned' => null, 'dkim_aligned' => null]);
        DB::table('dmarc_reports')->update(['aspf' => null, 'adkim' => null]);
    }

    private function firstNodeValue(\SimpleXMLElement $root, string $path): ?string
    {
        $result = $root->xpath($path);

        if (! is_array($result) || ! isset($result[0])) {
            return null;
        }

        $value = trim((string) $result[0]);

        return $value === '' ? null : $value;
    }

    private function isAligned(?string $headerFromDomain, ?string $authDomain, ?string $mode): ?bool
    {
        $headerFromDomain = $this->normalizeDomain($headerFromDomain);
        $authDomain = $this->normalizeDomain($authDomain);

        if ($headerFromDomain === null || $authDomain === null) {
            return null;
        }

        if ($headerFromDomain === $authDomain) {
            return true;
        }

        if ($mode === 's') {
            return false;
        }

        $headerFromOrgDomain = $this->organizationalDomain($headerFromDomain);
        $authOrgDomain = $this->organizationalDomain($authDomain);

        if ($headerFromOrgDomain === null || $authOrgDomain === null) {
            return false;
        }

        return $headerFromOrgDomain === $authOrgDomain;
    }

    private function normalizeDomain(?string $domain): ?string
    {
        $domain = strtolower(trim((string) $domain));

        return $domain === '' ? null : $domain;
    }

    private function organizationalDomain(string $domain): ?string
    {
        $rules = $this->rules();

        if (! $rules) {
            return null;
        }

        try {
            return $rules->resolve($domain)->registrableDomain()->toString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function rules(): ?Rules
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        if ($this->rulesLoadAttempted) {
            return null;
        }

        $this->rulesLoadAttempted = true;
        $path = storage_path('app/dns/public_suffix_list.dat');

        if (! is_file($path)) {
            return null;
        }

        try {
            $this->rules = Rules::fromPath($path);
        } catch (\Throwable) {
            return null;
        }

        return $this->rules;
    }
};
