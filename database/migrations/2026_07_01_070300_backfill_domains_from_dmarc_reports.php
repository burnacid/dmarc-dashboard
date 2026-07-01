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
        $now = now();

        $fromReports = DB::table('dmarc_reports')
            ->whereNotNull('policy_domain')
            ->where('policy_domain', '!=', '')
            ->distinct()
            ->pluck('policy_domain');

        $fromRecords = DB::table('dmarc_records')
            ->whereNotNull('header_from')
            ->where('header_from', '!=', '')
            ->distinct()
            ->pluck('header_from');

        $names = $fromReports->merge($fromRecords)
            ->map(fn ($domain) => strtolower(trim((string) $domain)))
            ->filter(fn (string $domain): bool => $domain !== '')
            ->unique()
            ->values();

        $rows = $names->map(fn (string $name) => [
            'name' => $name,
            'organization_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('domains')->insertOrIgnore($chunk);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data-only backfill; intentionally left non-reversible.
    }
};
