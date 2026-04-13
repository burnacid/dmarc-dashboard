<?php

namespace App\Services\Dns;

class NativeTxtRecordResolver implements TxtRecordResolver
{
    /**
     * @return list<string>
     */
    public function resolveTxtRecords(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        return collect($records)
            ->map(function (array $record): string {
                $entry = trim((string) ($record['txt'] ?? ''));

                if ($entry !== '') {
                    return $entry;
                }

                $segments = $record['entries'] ?? [];

                if (! is_array($segments)) {
                    return '';
                }

                return trim(implode('', array_map('strval', $segments)));
            })
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }
}

