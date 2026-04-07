<?php

namespace Tests\Unit;

use App\Models\DmarcReport;
use App\Models\ImapAccount;
use App\Models\User;
use App\Services\Dmarc\DmarcAttachmentExtractor;
use App\Services\Dmarc\DmarcIngestionService;
use App\Services\Dmarc\DmarcXmlParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use stdClass;
use Tests\TestCase;

class DmarcIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_flagged_spam_messages_to_the_error_folder_without_importing_them(): void
    {
        $account = $this->makeAccount([
            'processed_folder' => 'DMARC/Processed',
            'error_folder' => 'DMARC/Errors',
        ]);

        $message = new FakeImapMessage(
            messageId: 'spam-flagged-1',
            subject: 'Spam flagged message',
            flags: ['$Junk'],
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $account->refresh();

        $this->assertSame([
            'processed_messages' => 1,
            'imported_reports' => 0,
            'errors' => 0,
            'moved_messages' => 1,
        ], $stats);
        $this->assertSame('DMARC/Errors', $message->movedTo);
        $this->assertNull($message->textBodyReadAt);
        $this->assertSame([], $message->flagsSet);
        $this->assertDatabaseCount('dmarc_reports', 0);
        $this->assertNotNull($account->last_polled_at);
    }

    public function test_it_moves_header_marked_spam_messages_to_the_error_folder_without_importing_them(): void
    {
        $account = $this->makeAccount([
            'error_folder' => 'DMARC/Errors',
        ]);

        $message = new FakeImapMessage(
            messageId: 'spam-header-1',
            subject: 'Spam header message',
            headers: [
                'X-Spam-Flag' => 'YES',
            ],
            textBody: $this->validDmarcXml('header-spam-report'),
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $this->assertSame(1, $stats['processed_messages']);
        $this->assertSame(0, $stats['imported_reports']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame(1, $stats['moved_messages']);
        $this->assertSame('DMARC/Errors', $message->movedTo);
        $this->assertNull($message->textBodyReadAt);
        $this->assertSame([], $message->flagsSet);
        $this->assertDatabaseCount('dmarc_reports', 0);
    }

    public function test_it_still_imports_non_spam_dmarc_messages_and_moves_them_to_processed(): void
    {
        $account = $this->makeAccount([
            'processed_folder' => 'DMARC/Processed',
            'error_folder' => 'DMARC/Errors',
        ]);

        $message = new FakeImapMessage(
            messageId: 'valid-report-1',
            subject: 'Daily aggregate report',
            textBody: $this->validDmarcXml('valid-report-1'),
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $this->assertSame(1, $stats['processed_messages']);
        $this->assertSame(1, $stats['imported_reports']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame(1, $stats['moved_messages']);
        $this->assertSame('DMARC/Processed', $message->movedTo);
        $this->assertNotNull($message->textBodyReadAt);
        $this->assertSame(['Seen'], $message->flagsSet);

        $report = DmarcReport::query()->first();

        $this->assertNotNull($report);
        $this->assertSame($account->id, $report->imap_account_id);
        $this->assertSame('valid-report-1', $report->external_report_id);
        $this->assertSame('example.com', $report->policy_domain);
        $this->assertCount(1, $report->records);
    }

    public function test_it_counts_spam_messages_as_errors_when_no_error_folder_is_configured(): void
    {
        $account = $this->makeAccount([
            'error_folder' => null,
        ]);

        $message = new FakeImapMessage(
            messageId: 'spam-without-folder-1',
            subject: 'Spam without target folder',
            flags: ['Spam'],
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $this->assertSame(1, $stats['processed_messages']);
        $this->assertSame(0, $stats['imported_reports']);
        $this->assertSame(1, $stats['errors']);
        $this->assertSame(0, $stats['moved_messages']);
        $this->assertNull($message->movedTo);
        $this->assertSame([], $message->flagsSet);
        $this->assertDatabaseCount('dmarc_reports', 0);
    }

    public function test_it_moves_messages_without_a_detectable_dmarc_payload_to_the_error_folder(): void
    {
        $account = $this->makeAccount([
            'error_folder' => 'DMARC/Errors',
        ]);

        $message = new FakeImapMessage(
            messageId: 'no-payload-1',
            subject: 'FW: YOU PERVERT, I RECORDED YOU!',
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $this->assertSame(1, $stats['processed_messages']);
        $this->assertSame(0, $stats['imported_reports']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame(1, $stats['moved_messages']);
        $this->assertSame('DMARC/Errors', $message->movedTo);
        $this->assertNotNull($message->textBodyReadAt);
        $this->assertSame([], $message->flagsSet);
        $this->assertDatabaseCount('dmarc_reports', 0);
    }

    public function test_it_counts_messages_without_a_detectable_dmarc_payload_as_errors_when_no_error_folder_is_configured(): void
    {
        $account = $this->makeAccount([
            'error_folder' => null,
        ]);

        $message = new FakeImapMessage(
            messageId: 'no-payload-without-folder-1',
            subject: 'Unexpected mailbox message',
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $this->assertSame(1, $stats['processed_messages']);
        $this->assertSame(0, $stats['imported_reports']);
        $this->assertSame(1, $stats['errors']);
        $this->assertSame(0, $stats['moved_messages']);
        $this->assertNull($message->movedTo);
        $this->assertNotNull($message->textBodyReadAt);
        $this->assertSame([], $message->flagsSet);
        $this->assertDatabaseCount('dmarc_reports', 0);
    }

    public function test_it_marks_imported_messages_as_read_even_without_a_processed_folder(): void
    {
        $account = $this->makeAccount([
            'processed_folder' => null,
        ]);

        $message = new FakeImapMessage(
            messageId: 'valid-report-without-folder-1',
            subject: 'Aggregate report without processed folder',
            textBody: $this->validDmarcXml('valid-report-without-folder-1'),
        );

        $stats = $this->makeService(new FakeImapClient([$message]))->pollAccount($account);

        $this->assertSame(1, $stats['processed_messages']);
        $this->assertSame(1, $stats['imported_reports']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame(0, $stats['moved_messages']);
        $this->assertNull($message->movedTo);
        $this->assertSame(['Seen'], $message->flagsSet);
    }

    private function makeAccount(array $overrides = []): ImapAccount
    {
        $user = User::factory()->create();

        return ImapAccount::query()->create(array_merge([
            'user_id' => $user->id,
            'name' => 'Mailbox',
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'reports@example.com',
            'password' => 'secret-app-password',
            'folder' => 'INBOX',
            'processed_folder' => null,
            'error_folder' => 'DMARC/Errors',
            'search_criteria' => 'UNSEEN',
            'is_active' => true,
        ], $overrides));
    }

    private function makeService(FakeImapClient $client): DmarcIngestionService
    {
        return new class(app(DmarcAttachmentExtractor::class), app(DmarcXmlParser::class), $client) extends DmarcIngestionService {
            public function __construct(
                DmarcAttachmentExtractor $attachmentExtractor,
                DmarcXmlParser $xmlParser,
                private readonly FakeImapClient $client,
            ) {
                parent::__construct($attachmentExtractor, $xmlParser);
            }

            protected function createClient(ImapAccount $account): mixed
            {
                return $this->client;
            }
        };
    }

    private function validDmarcXml(string $reportId): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<feedback>
  <report_metadata>
    <org_name>Example Receiver</org_name>
    <email>dmarc@example.net</email>
    <report_id>{$reportId}</report_id>
    <date_range>
      <begin>1711843200</begin>
      <end>1711929600</end>
    </date_range>
  </report_metadata>
  <policy_published>
    <domain>example.com</domain>
  </policy_published>
  <record>
    <row>
      <source_ip>192.0.2.15</source_ip>
      <count>12</count>
      <policy_evaluated>
        <disposition>none</disposition>
        <dkim>pass</dkim>
        <spf>pass</spf>
      </policy_evaluated>
    </row>
    <identifiers>
      <header_from>example.com</header_from>
    </identifiers>
    <auth_results>
      <dkim>
        <domain>mail.example.com</domain>
        <result>pass</result>
      </dkim>
      <spf>
        <domain>bounce.example.net</domain>
        <result>pass</result>
      </spf>
    </auth_results>
  </record>
</feedback>
XML;
    }
}

class FakeImapClient
{
    public bool $connected = false;

    public bool $disconnected = false;

    /**
     * @param array<int, FakeImapMessage> $messages
     */
    public function __construct(private readonly array $messages)
    {
    }

    public function connect(): void
    {
        $this->connected = true;
    }

    public function getFolder(string $folder): FakeImapFolder
    {
        return new FakeImapFolder($folder, $this->messages);
    }

    public function disconnect(): void
    {
        $this->disconnected = true;
    }
}

class FakeImapFolder
{
    /**
     * @param array<int, FakeImapMessage> $messages
     */
    public function __construct(
        public readonly string $name,
        private readonly array $messages,
    ) {
    }

    public function messages(): FakeImapQuery
    {
        return new FakeImapQuery($this->messages);
    }
}

class FakeImapQuery
{
    public ?string $appliedCriteria = null;

    /**
     * @param array<int, FakeImapMessage> $messages
     */
    public function __construct(private readonly array $messages)
    {
    }

    public function all(): self
    {
        $this->appliedCriteria = 'ALL';

        return $this;
    }

    public function seen(): self
    {
        $this->appliedCriteria = 'SEEN';

        return $this;
    }

    public function unseen(): self
    {
        $this->appliedCriteria = 'UNSEEN';

        return $this;
    }

    public function answered(): self
    {
        $this->appliedCriteria = 'ANSWERED';

        return $this;
    }

    public function unanswered(): self
    {
        $this->appliedCriteria = 'UNANSWERED';

        return $this;
    }

    public function where(string $criteria): self
    {
        $this->appliedCriteria = $criteria;

        return $this;
    }

    public function chunked(callable $callback, int $chunkSize): void
    {
        $callback(new Collection($this->messages));
    }
}

class FakeImapMessage
{
    public array $attachments = [];

    public array $flagsSet = [];

    public ?string $movedTo = null;

    public ?string $textBodyReadAt = null;

    /**
     * @param array<int, string> $flags
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $messageId,
        private readonly string $subject,
        private readonly array $flags = [],
        private readonly array $headers = [],
        private readonly string $textBody = '',
        private readonly string $htmlBody = '',
    ) {
    }

    public function hasFlag(string $flag): bool
    {
        $normalizedFlags = array_map(
            static fn (string $value): string => str_replace('\\', '', strtolower($value)),
            $this->flags,
        );

        return in_array(str_replace('\\', '', strtolower($flag)), $normalizedFlags, true);
    }

    public function move(string $folder): object
    {
        $this->movedTo = $folder;

        return new stdClass();
    }

    public function setFlag(string $flag): bool
    {
        $this->flagsSet[] = $flag;

        return true;
    }

    public function getHeader(): FakeImapHeader
    {
        return new FakeImapHeader($this->headers);
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTextBody(): string
    {
        $this->textBodyReadAt = now()->toIso8601String();

        return $this->textBody;
    }

    public function getHTMLBody(): string
    {
        return $this->htmlBody;
    }
}

class FakeImapHeader
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    public function get(string $header): string
    {
        $normalizedHeader = str_replace(['-', ' '], '_', strtolower($header));

        foreach ($this->values as $name => $value) {
            if (str_replace(['-', ' '], '_', strtolower($name)) === $normalizedHeader) {
                return $value;
            }
        }

        return '';
    }
}

