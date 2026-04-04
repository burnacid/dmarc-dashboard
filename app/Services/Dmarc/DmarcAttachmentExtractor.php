<?php

namespace App\Services\Dmarc;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class DmarcAttachmentExtractor
{
    /**
     * @param  array<int, array{name?:string,content:string,content_type?:string}>  $attachments
     * @return array<int, string>
     */
    public function extractXmlPayloads(array $attachments): array
    {
        $xmlPayloads = [];

        foreach ($attachments as $attachment) {
            $name = strtolower((string) ($attachment['name'] ?? ''));
            $contentType = strtolower((string) ($attachment['content_type'] ?? ''));
            $content = $attachment['content'];

            foreach ($this->extractPayloadCandidates($content, $name, $contentType) as $xmlFromAttachment) {
                if ($this->isDmarcXml($xmlFromAttachment)) {
                    $xmlPayloads[] = $xmlFromAttachment;
                }
            }
        }

        return array_values(array_unique($xmlPayloads));
    }

    /**
     * @return array<int, string>
     */
    private function extractPayloadCandidates(string $payload, string $name, string $contentType, int $depth = 0): array
    {
        if ($payload === '' || $depth > 2) {
            return [];
        }

        if ($this->isXmlLike($payload, $contentType, $name)) {
            return [$payload];
        }

        if ($this->isGzipLike($payload, $contentType, $name)) {
            $decoded = @gzdecode($payload);

            if (is_string($decoded) && $decoded !== '') {
                return $this->extractPayloadCandidates($decoded, $name, 'application/xml', $depth + 1);
            }

            // Fallback: try base64-decoding then gzip
            $b64decoded = base64_decode($payload, true);
            if ($b64decoded !== false) {
                $decoded = @gzdecode($b64decoded);
                if (is_string($decoded) && $decoded !== '') {
                    return $this->extractPayloadCandidates($decoded, $name, 'application/xml', $depth + 1);
                }
            }
        }

        if ($this->isZipLike($payload, $contentType, $name)) {
            $payloads = [];

            foreach ($this->extractFromZip($payload) as $entry) {
                $payloads = array_merge($payloads, $this->extractPayloadCandidates(
                    $entry['content'],
                    $entry['name'],
                    'application/octet-stream',
                    $depth + 1
                ));
            }

            if ($payloads !== []) {
                return $payloads;
            }

            // Fallback: content may still be base64-encoded — try decoding it first
            $b64decoded = base64_decode($payload, true);
            if ($b64decoded !== false && str_starts_with($b64decoded, "PK\x03\x04")) {
                foreach ($this->extractFromZip($b64decoded) as $entry) {
                    $payloads = array_merge($payloads, $this->extractPayloadCandidates(
                        $entry['content'],
                        $entry['name'],
                        'application/octet-stream',
                        $depth + 1
                    ));
                }
            }

            return $payloads;
        }

        // Some providers inline XML directly in the message body without attachment metadata.
        if (str_contains($payload, '<feedback')) {
            return [$payload];
        }

        return [];
    }

    private function isXmlLike(string $payload, string $contentType, string $name): bool
    {
        $trimmed = ltrim($payload);

        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<feedback')) {
            return true;
        }

        return str_ends_with($name, '.xml')
            || str_contains($contentType, 'xml')
            || str_contains($contentType, 'text/plain');
    }

    private function isGzipLike(string $payload, string $contentType, string $name): bool
    {
        return str_ends_with($name, '.gz')
            || str_contains($contentType, 'gzip')
            || str_starts_with($payload, "\x1f\x8b");
    }

    private function isZipLike(string $payload, string $contentType, string $name): bool
    {
        return str_ends_with($name, '.zip')
            || str_contains($contentType, 'zip')
            || str_starts_with($payload, "PK\x03\x04");
    }

    private function isDmarcXml(string $xml): bool
    {
        $trimmed = ltrim($xml);

        return str_starts_with($trimmed, '<feedback') || str_contains($trimmed, '<feedback');
    }

    /**
     * @return array<int, array{name:string,content:string}>
     */
    private function extractFromZip(string $rawZip): array
    {
        if (! class_exists(ZipArchive::class)) {
            Log::warning('ZipArchive extension is unavailable; cannot inspect ZIP attachment for DMARC payloads.');

            return [];
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'dmarc_zip_');

        if ($tmpPath === false) {
            throw new RuntimeException('Failed to create temp file for ZIP parsing.');
        }

        if (file_put_contents($tmpPath, $rawZip) === false) {
            @unlink($tmpPath);

            throw new RuntimeException('Failed to write temp ZIP payload for parsing.');
        }

        $zip = new ZipArchive();
        $entries = [];

        $openResult = $zip->open($tmpPath);

        if ($openResult !== true) {
            @unlink($tmpPath);

            Log::debug('ZipArchive could not open temp file.', [
                'error_code' => $openResult,
                'bytes' => strlen($rawZip),
                'magic' => bin2hex(substr($rawZip, 0, 4)),
            ]);

            return [];
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = strtolower((string) $zip->getNameIndex($i));
            $entryContent = $zip->getFromIndex($i);

            if (! is_string($entryContent) || $entryContent === '') {
                continue;
            }

            $entries[] = [
                'name' => $entryName,
                'content' => $entryContent,
            ];
        }

        $zip->close();
        @unlink($tmpPath);

        return $entries;
    }
}

