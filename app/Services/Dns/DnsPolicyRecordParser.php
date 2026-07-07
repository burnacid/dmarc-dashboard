<?php

namespace App\Services\Dns;

class DnsPolicyRecordParser
{
    private const DMARC_TAGS = [
        'v' => 'Version',
        'p' => 'Policy',
        'sp' => 'Subdomain policy',
        'pct' => 'Percentage',
        'rua' => 'Aggregate report URI',
        'ruf' => 'Forensic report URI',
        'adkim' => 'DKIM alignment',
        'aspf' => 'SPF alignment',
        'fo' => 'Failure options',
        'ri' => 'Report interval',
    ];

    private const DKIM_TAGS = [
        'v' => 'Version',
        'k' => 'Key type',
        'h' => 'Hash algorithms',
        'p' => 'Public key',
        's' => 'Service type',
        't' => 'Flags',
        'g' => 'Granularity',
        'n' => 'Notes',
    ];

    private const SPF_QUALIFIER_LABELS = [
        '+' => 'Pass',
        '-' => 'Fail',
        '~' => 'SoftFail',
        '?' => 'Neutral',
    ];

    /**
     * @return list<array{tag:string,label:string,value:string}>
     */
    public function parse(string $type, string $record): array
    {
        return match ($type) {
            'dmarc' => $this->describe('dmarc', $this->splitTagValuePairs($record)),
            'dkim' => $this->describe('dkim', $this->splitTagValuePairs($record)),
            'spf' => $this->parseSpfRecord($record),
            default => [],
        };
    }

    /**
     * Labels already tag => value pairs (e.g. extracted from a report's
     * <policy_published> XML block) using the same tag dictionaries used
     * for DNS TXT record parsing.
     *
     * @param  array<string, string>  $tagValues
     * @return list<array{tag:string,label:string,value:string}>
     */
    public function describe(string $type, array $tagValues): array
    {
        $labels = match ($type) {
            'dmarc' => self::DMARC_TAGS,
            'dkim' => self::DKIM_TAGS,
            default => [],
        };

        $result = [];

        foreach ($tagValues as $tag => $value) {
            $tag = strtolower(trim((string) $tag));
            $value = trim((string) $value);

            if ($tag === '' || $value === '') {
                continue;
            }

            $result[] = [
                'tag' => $tag,
                'label' => $labels[$tag] ?? strtoupper($tag),
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function splitTagValuePairs(string $record): array
    {
        $pairs = [];

        foreach (explode(';', $record) as $segment) {
            $segment = trim($segment);

            if ($segment === '' || ! str_contains($segment, '=')) {
                continue;
            }

            [$tag, $value] = array_map('trim', explode('=', $segment, 2));
            $pairs[strtolower($tag)] = $value;
        }

        return $pairs;
    }

    /**
     * @return list<array{tag:string,label:string,value:string}>
     */
    private function parseSpfRecord(string $record): array
    {
        $terms = preg_split('/\s+/', trim($record)) ?: [];
        $result = [];

        foreach ($terms as $term) {
            if ($term === '') {
                continue;
            }

            if (stripos($term, 'v=spf1') === 0) {
                $result[] = ['tag' => 'v', 'label' => 'Version', 'value' => $term];

                continue;
            }

            $qualifier = '+';
            if (isset(self::SPF_QUALIFIER_LABELS[$term[0]])) {
                $qualifier = $term[0];
                $term = substr($term, 1);
            }

            if ($term === '') {
                continue;
            }

            $result[] = [
                'tag' => 'mechanism',
                'label' => self::SPF_QUALIFIER_LABELS[$qualifier],
                'value' => $term,
            ];
        }

        return $result;
    }
}
