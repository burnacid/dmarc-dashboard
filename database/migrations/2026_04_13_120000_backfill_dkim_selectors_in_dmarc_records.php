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
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('dmarc_records')
                    ->whereColumn('dmarc_records.dmarc_report_id', 'dmarc_reports.id')
                    ->where(function ($selectorQuery): void {
                        $selectorQuery->whereNull('dmarc_records.dkim_selector')
                            ->orWhere('dmarc_records.dkim_selector', '');
                    });
            })
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

                    $selectorsByIndex = [];

                    foreach ($root->record ?? [] as $xmlRecord) {
                        $selectorsByIndex[] = $this->firstNodeValue($xmlRecord, 'auth_results/dkim/selector');
                    }

                    if ($selectorsByIndex === []) {
                        continue;
                    }

                    $records = DB::table('dmarc_records')
                        ->where('dmarc_report_id', $report->id)
                        ->orderBy('id')
                        ->get(['id', 'dkim_selector']);

                    foreach ($records as $index => $record) {
                        if ($record->dkim_selector !== null && $record->dkim_selector !== '') {
                            continue;
                        }

                        $selector = $selectorsByIndex[$index] ?? null;

                        if ($selector === null) {
                            continue;
                        }

                        DB::table('dmarc_records')
                            ->where('id', $record->id)
                            ->update(['dkim_selector' => $selector]);
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

