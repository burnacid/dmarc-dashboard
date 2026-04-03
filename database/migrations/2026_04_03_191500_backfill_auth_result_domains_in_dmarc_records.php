<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

                    $parsedByIndex = [];

                    foreach ($root->record ?? [] as $xmlRecord) {
                        $parsedByIndex[] = [
                            'dkim_domain' => $this->firstNodeValue($xmlRecord, 'auth_results/dkim/domain'),
                            'spf_domain' => $this->firstNodeValue($xmlRecord, 'auth_results/spf/domain'),
                        ];
                    }

                    if ($parsedByIndex === []) {
                        continue;
                    }

                    $records = DB::table('dmarc_records')
                        ->where('dmarc_report_id', $report->id)
                        ->orderBy('id')
                        ->get(['id', 'dkim_domain', 'spf_domain']);

                    foreach ($records as $index => $record) {
                        $parsed = $parsedByIndex[$index] ?? null;

                        if (! is_array($parsed)) {
                            continue;
                        }

                        $updates = [];

                        if (($record->dkim_domain === null || $record->dkim_domain === '') && ($parsed['dkim_domain'] ?? null) !== null) {
                            $updates['dkim_domain'] = $parsed['dkim_domain'];
                        }

                        if (($record->spf_domain === null || $record->spf_domain === '') && ($parsed['spf_domain'] ?? null) !== null) {
                            $updates['spf_domain'] = $parsed['spf_domain'];
                        }

                        if ($updates !== []) {
                            DB::table('dmarc_records')
                                ->where('id', $record->id)
                                ->update($updates);
                        }
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data backfill.
    }

    private function firstNodeValue(\SimpleXMLElement $record, string $path): ?string
    {
        $result = $record->xpath($path);

        if (! is_array($result) || ! isset($result[0])) {
            return null;
        }

        $value = trim((string) $result[0]);

        return $value === '' ? null : $value;
    }
};

