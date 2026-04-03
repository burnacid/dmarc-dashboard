<?php

namespace App\Services\Dmarc;

use Carbon\Carbon;
use RuntimeException;
use SimpleXMLElement;

class DmarcXmlParser
{
    /**
     * @return array{
     *   external_report_id:string,
     *   org_name:?string,
     *   email:?string,
     *   report_begin_at:?Carbon,
     *   report_end_at:?Carbon,
     *   policy_domain:?string,
     *   records:array<int, array{source_ip:string,message_count:int,disposition:?string,dkim:?string,dkim_domain:?string,spf:?string,spf_domain:?string,header_from:?string}>
     * }
     */
    public function parse(string $xml): array
    {
        libxml_use_internal_errors(true);
        $root = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);

        if (! $root instanceof SimpleXMLElement) {
            throw new RuntimeException('Unable to parse DMARC XML payload.');
        }

        $reportId = $this->readNode($root, 'report_metadata/report_id');
        $beginEpoch = $this->readNode($root, 'report_metadata/date_range/begin');
        $endEpoch = $this->readNode($root, 'report_metadata/date_range/end');

        $records = [];
        foreach ($root->record ?? [] as $record) {
            $sourceIp = (string) ($record->row->source_ip ?? '');
            if ($sourceIp === '') {
                continue;
            }

            $records[] = [
                'source_ip' => $sourceIp,
                'message_count' => (int) ($record->row->count ?? 0),
                'disposition' => $this->nullableString($record->row->policy_evaluated->disposition ?? null),
                'dkim' => $this->nullableString($record->row->policy_evaluated->dkim ?? null),
                'dkim_domain' => $this->firstAuthResultValue($record, 'dkim', 'domain'),
                'spf' => $this->nullableString($record->row->policy_evaluated->spf ?? null),
                'spf_domain' => $this->firstAuthResultValue($record, 'spf', 'domain'),
                'header_from' => $this->nullableString($record->identifiers->header_from ?? null),
            ];
        }

        return [
            'external_report_id' => $reportId ?: sha1($xml),
            'org_name' => $this->readNode($root, 'report_metadata/org_name'),
            'email' => $this->readNode($root, 'report_metadata/email'),
            'report_begin_at' => is_numeric($beginEpoch) ? Carbon::createFromTimestampUTC((int) $beginEpoch) : null,
            'report_end_at' => is_numeric($endEpoch) ? Carbon::createFromTimestampUTC((int) $endEpoch) : null,
            'policy_domain' => $this->readNode($root, 'policy_published/domain'),
            'records' => $records,
        ];
    }

    private function readNode(SimpleXMLElement $root, string $path): ?string
    {
        $result = $root->xpath($path);
        if (! is_array($result) || ! isset($result[0])) {
            return null;
        }

        $value = trim((string) $result[0]);

        return $value === '' ? null : $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function firstAuthResultValue(SimpleXMLElement $record, string $type, string $field): ?string
    {
        $result = $record->xpath("auth_results/{$type}/{$field}");

        if (! is_array($result) || ! isset($result[0])) {
            return null;
        }

        return $this->nullableString($result[0]);
    }
}

