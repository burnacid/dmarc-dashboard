<?php

namespace Tests\Unit;

use App\Services\Dns\DnsPolicyRecordParser;
use Tests\TestCase;

class DnsPolicyRecordParserTest extends TestCase
{
    public function test_it_parses_dmarc_tags(): void
    {
        $parser = new DnsPolicyRecordParser;

        $result = $parser->parse('dmarc', 'v=DMARC1; p=reject; sp=quarantine; pct=100; rua=mailto:dmarc@example.com');

        $this->assertSame([
            ['tag' => 'v', 'label' => 'Version', 'value' => 'DMARC1'],
            ['tag' => 'p', 'label' => 'Policy', 'value' => 'reject'],
            ['tag' => 'sp', 'label' => 'Subdomain policy', 'value' => 'quarantine'],
            ['tag' => 'pct', 'label' => 'Percentage', 'value' => '100'],
            ['tag' => 'rua', 'label' => 'Aggregate report URI', 'value' => 'mailto:dmarc@example.com'],
        ], $result);
    }

    public function test_it_labels_unknown_dmarc_tags_by_uppercasing_the_tag(): void
    {
        $parser = new DnsPolicyRecordParser;

        $result = $parser->parse('dmarc', 'v=DMARC1; unknown=value');

        $this->assertSame([
            ['tag' => 'v', 'label' => 'Version', 'value' => 'DMARC1'],
            ['tag' => 'unknown', 'label' => 'UNKNOWN', 'value' => 'value'],
        ], $result);
    }

    public function test_it_parses_dkim_tags(): void
    {
        $parser = new DnsPolicyRecordParser;

        $result = $parser->parse('dkim', 'v=DKIM1; k=rsa; p=ABC123');

        $this->assertSame([
            ['tag' => 'v', 'label' => 'Version', 'value' => 'DKIM1'],
            ['tag' => 'k', 'label' => 'Key type', 'value' => 'rsa'],
            ['tag' => 'p', 'label' => 'Public key', 'value' => 'ABC123'],
        ], $result);
    }

    public function test_it_parses_spf_mechanisms_with_qualifiers(): void
    {
        $parser = new DnsPolicyRecordParser;

        $result = $parser->parse('spf', 'v=spf1 include:_spf.example.net ~mx -all');

        $this->assertSame([
            ['tag' => 'v', 'label' => 'Version', 'value' => 'v=spf1'],
            ['tag' => 'mechanism', 'label' => 'Pass', 'value' => 'include:_spf.example.net'],
            ['tag' => 'mechanism', 'label' => 'SoftFail', 'value' => 'mx'],
            ['tag' => 'mechanism', 'label' => 'Fail', 'value' => 'all'],
        ], $result);
    }

    public function test_it_ignores_blank_segments_and_stray_whitespace(): void
    {
        $parser = new DnsPolicyRecordParser;

        $this->assertSame([], $parser->parse('dmarc', ''));
        $this->assertSame([], $parser->parse('spf', '   '));
        $this->assertSame([], $parser->parse('unknown-type', 'v=DMARC1; p=reject'));
    }
}
