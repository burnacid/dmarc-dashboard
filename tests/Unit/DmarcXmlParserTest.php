<?php

namespace Tests\Unit;

use App\Services\Dmarc\DmarcXmlParser;
use Carbon\Carbon;
use Tests\TestCase;

class DmarcXmlParserTest extends TestCase
{
    public function test_it_parses_a_dmarc_aggregate_report(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<feedback>
  <report_metadata>
    <org_name>Example Receiver</org_name>
    <email>dmarc@example.net</email>
    <report_id>abc-123</report_id>
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
        <selector>s1</selector>
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

        $parsed = app(DmarcXmlParser::class)->parse($xml);

        $this->assertSame('abc-123', $parsed['external_report_id']);
        $this->assertSame('Example Receiver', $parsed['org_name']);
        $this->assertSame('dmarc@example.net', $parsed['email']);
        $this->assertSame('example.com', $parsed['policy_domain']);
        $this->assertInstanceOf(Carbon::class, $parsed['report_begin_at']);
        $this->assertInstanceOf(Carbon::class, $parsed['report_end_at']);
        $this->assertCount(1, $parsed['records']);
        $this->assertSame('192.0.2.15', $parsed['records'][0]['source_ip']);
        $this->assertSame(12, $parsed['records'][0]['message_count']);
        $this->assertSame('pass', $parsed['records'][0]['dkim']);
        $this->assertSame('mail.example.com', $parsed['records'][0]['dkim_domain']);
        $this->assertSame('s1', $parsed['records'][0]['dkim_selector']);
        $this->assertSame('bounce.example.net', $parsed['records'][0]['spf_domain']);
    }

    public function test_it_falls_back_to_a_hash_when_report_id_is_missing(): void
    {
        $xml = '<feedback><report_metadata><org_name>Receiver</org_name></report_metadata></feedback>';

        $parsed = app(DmarcXmlParser::class)->parse($xml);

        $this->assertSame(sha1($xml), $parsed['external_report_id']);
        $this->assertSame([], $parsed['records']);
    }
}

