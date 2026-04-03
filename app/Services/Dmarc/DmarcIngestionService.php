<?php

namespace App\Services\Dmarc;

use App\Models\DmarcReport;
use App\Models\ImapAccount;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ResponseException;

class DmarcIngestionService
{
    private const CHUNK_SIZE = 50;

    public function __construct(
        private readonly DmarcAttachmentExtractor $attachmentExtractor,
        private readonly DmarcXmlParser $xmlParser,
    ) {
    }

    /**
     * @return array{processed_messages:int,imported_reports:int,errors:int,moved_messages:int}
     */
    public function pollAccount(ImapAccount $account): array
    {
        $stats = [
            'processed_messages' => 0,
            'imported_reports' => 0,
            'errors' => 0,
            'moved_messages' => 0,
        ];

        try {
            $clientManager = new ClientManager();
            $client = $clientManager->make([
                'host' => $account->host,
                'port' => $account->port,
                'encryption' => $account->encryption === 'none' ? false : $account->encryption,
                'validate_cert' => true,
                'username' => $account->username,
                'password' => $account->password,
                'protocol' => 'imap',
                'timeout' => 300,
                'fetch' => 'ALL',
            ]);

            $client->connect();

            $folder = $client->getFolder($account->folder ?: 'INBOX');
            $query = $folder->messages();
            $query = $this->applySearchCriteria($query, $account->search_criteria ?: 'UNSEEN');

            try {
                $query->chunked(function ($messages) use ($account, &$stats): void {
                    // Process messages in reverse order (newest first)
                    foreach ($messages->reverse() as $message) {
                        $stats['processed_messages']++;

                        try {
                            $importsFromMessage = $this->importMessage($account, $message);
                            $stats['imported_reports'] += $importsFromMessage;

                            if ($importsFromMessage > 0 && filled($account->processed_folder)) {
                                if ($message->move($account->processed_folder) !== null) {
                                    $stats['moved_messages']++;
                                } else {
                                    $stats['errors']++;

                                    Log::warning('DMARC message imported but could not be moved.', [
                                        'imap_account_id' => $account->id,
                                        'target_folder' => $account->processed_folder,
                                        'message_id' => $this->messageId($message),
                                    ]);
                                }
                            }
                        } catch (Throwable $exception) {
                            $stats['errors']++;

                            Log::warning('Failed to process DMARC message.', [
                                'imap_account_id' => $account->id,
                                'message_id' => $this->messageId($message),
                                'error' => $exception->getMessage(),
                            ]);
                        } finally {
                            unset($message);
                        }
                    }

                    gc_collect_cycles();
                }, self::CHUNK_SIZE);
            } catch (ResponseException $responseException) {
                $stats['errors']++;

                Log::error('IMAP response error during polling.', [
                    'imap_account_id' => $account->id,
                    'error' => $responseException->getMessage(),
                    'processed_so_far' => $stats['processed_messages'],
                ]);

                // Continue gracefully - don't completely fail just because of a response error
                // This can happen with flaky connections or servers that disconnect mid-query
            }

            $account->forceFill(['last_polled_at' => now()])->save();
            $client->disconnect();
        } catch (Throwable $exception) {
            Log::error('IMAP polling failed for account.', [
                'imap_account_id' => $account->id,
                'error' => $exception->getMessage(),
                'exception_type' => get_class($exception),
            ]);

            $stats['errors']++;
        }

        return $stats;
    }

    private function importMessage(ImapAccount $account, mixed $message): int
    {
        $attachments = [];
        $attachmentMeta = [];
        $messageAttachments = $message->attachments
            ?? (method_exists($message, 'getAttachments') ? $message->getAttachments() : []);

        foreach ($messageAttachments ?? [] as $attachment) {
            $name = $this->attachmentName($attachment);
            $contentType = $this->attachmentContentType($attachment);
            $content = $this->attachmentContent($attachment);

            $attachmentMeta[] = [
                'name' => $name,
                'content_type' => $contentType,
                'size' => strlen($content),
            ];

            if ($content !== '') {
                $attachments[] = [
                    'name' => $name,
                    'content_type' => $contentType,
                    'content' => $content,
                ];
            }
        }

        $xmlPayloads = $this->attachmentExtractor->extractXmlPayloads($attachments);

        if ($xmlPayloads === []) {
            $bodyCandidates = [
                method_exists($message, 'getTextBody') ? (string) $message->getTextBody() : '',
                method_exists($message, 'getHTMLBody') ? (string) $message->getHTMLBody() : '',
            ];

            foreach ($bodyCandidates as $bodyCandidate) {
                if ($bodyCandidate === '') {
                    continue;
                }

                $xmlPayloads = array_merge(
                    $xmlPayloads,
                    $this->attachmentExtractor->extractXmlPayloads([
                        ['name' => 'inline-body.xml', 'content_type' => 'text/plain', 'content' => $bodyCandidate],
                    ])
                );
            }
        }

        if ($xmlPayloads === []) {
            Log::debug('Message scanned with no detectable DMARC payload.', [
                'imap_account_id' => $account->id,
                'message_id' => $this->messageId($message),
                'subject' => $this->messageSubject($message),
                'attachments' => $attachmentMeta,
                'content_signatures' => array_map(fn ($a) => bin2hex(substr($a['content'], 0, 4)), $attachments),
            ]);

            return 0;
        }

        $importedReports = 0;

        foreach ($xmlPayloads as $xmlPayload) {
            $parsed = $this->xmlParser->parse($xmlPayload);

            $report = DmarcReport::updateOrCreate(
                [
                    'imap_account_id' => $account->id,
                    'external_report_id' => $parsed['external_report_id'],
                ],
                [
                    'org_name' => $parsed['org_name'],
                    'email' => $parsed['email'],
                    'report_begin_at' => $parsed['report_begin_at'],
                    'report_end_at' => $parsed['report_end_at'],
                    'policy_domain' => $parsed['policy_domain'],
                    'raw_xml' => $xmlPayload,
                ]
            );

            $report->records()->delete();
            $report->records()->createMany($parsed['records']);
            $importedReports++;
        }

        return $importedReports;
    }

    private function applySearchCriteria(mixed $query, string $criteria): mixed
    {
        $criteria = strtoupper(trim($criteria));

        return match ($criteria) {
            'ALL' => $query->all(),
            'SEEN' => $query->seen(),
            'UNSEEN' => $query->unseen(),
            'ANSWERED' => $query->answered(),
            'UNANSWERED' => $query->unanswered(),
            default => $query->where("CUSTOM {$criteria}"),
        };
    }

    private function attachmentName(mixed $attachment): string
    {
        if (is_object($attachment) && method_exists($attachment, 'getName')) {
            return (string) $attachment->getName();
        }

        if (is_object($attachment) && method_exists($attachment, 'name')) {
            return (string) $attachment->name();
        }

        if (is_object($attachment)) {
            $name = (string) ($attachment->name ?? '');

            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    private function attachmentContent(mixed $attachment): string
    {
        // Try the __call-based magic accessor first (works with Webklex Attachment)
        if (is_object($attachment)) {
            try {
                $content = $attachment->getContent();
                if (is_string($content) && $content !== '') {
                    return $content;
                }
            } catch (\Throwable) {
                // fall through to other strategies
            }
        }

        if (is_object($attachment) && method_exists($attachment, 'getContent')) {
            return (string) $attachment->getContent();
        }

        if (is_object($attachment) && method_exists($attachment, 'content')) {
            return (string) $attachment->content();
        }

        if (is_object($attachment)) {
            $content = (string) ($attachment->content ?? '');

            if ($content !== '') {
                return $content;
            }
        }

        return '';
    }

    private function attachmentContentType(mixed $attachment): string
    {
        if (is_object($attachment) && method_exists($attachment, 'getContentType')) {
            return (string) $attachment->getContentType();
        }

        if (is_object($attachment) && method_exists($attachment, 'contentType')) {
            return (string) $attachment->contentType();
        }

        if (is_object($attachment)) {
            $contentType = (string) ($attachment->content_type ?? '');

            if ($contentType !== '') {
                return $contentType;
            }
        }

        return '';
    }

    private function messageId(mixed $message): ?string
    {
        $messageId = method_exists($message, 'getMessageId')
            ? (string) $message->getMessageId()
            : (string) ($message->message_id ?? '');

        return $messageId !== '' ? $messageId : null;
    }

    private function messageSubject(mixed $message): ?string
    {
        $subject = method_exists($message, 'getSubject')
            ? (string) $message->getSubject()
            : (string) ($message->subject ?? '');

        return $subject !== '' ? $subject : null;
    }
}

