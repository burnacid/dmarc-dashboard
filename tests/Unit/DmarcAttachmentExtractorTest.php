<?php

namespace Tests\Unit;

use App\Services\Dmarc\DmarcAttachmentExtractor;
use Tests\TestCase;
use ZipArchive;

class DmarcAttachmentExtractorTest extends TestCase
{
    public function test_it_extracts_plain_xml_and_gzip_payloads(): void
    {
        $xml = '<feedback><report_metadata><report_id>1</report_id></report_metadata></feedback>';

        $payloads = app(DmarcAttachmentExtractor::class)->extractXmlPayloads([
            ['name' => 'report.xml', 'content' => $xml],
            ['name' => 'report.xml.gz', 'content' => gzencode($xml)],
        ]);

        $this->assertSame([$xml], $payloads);
    }

    public function test_it_extracts_xml_from_zip_when_ziparchive_is_available(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $xml = '<feedback><report_metadata><report_id>zip-report</report_id></report_metadata></feedback>';
        $tmpFile = tempnam(sys_get_temp_dir(), 'zip_test_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('report.xml', $xml);
        $zip->close();

        $binary = file_get_contents($tmpFile);
        @unlink($tmpFile);

        $payloads = app(DmarcAttachmentExtractor::class)->extractXmlPayloads([
            ['name' => 'report.zip', 'content' => $binary],
        ]);

        $this->assertSame([$xml], $payloads);
    }

    public function test_it_extracts_gzip_payload_without_filename_extension(): void
    {
        $xml = '<feedback><report_metadata><report_id>gzip-no-name</report_id></report_metadata></feedback>';

        $payloads = app(DmarcAttachmentExtractor::class)->extractXmlPayloads([
            ['name' => '', 'content_type' => 'application/octet-stream', 'content' => gzencode($xml)],
        ]);

        $this->assertSame([$xml], $payloads);
    }

    public function test_it_extracts_inline_feedback_xml_from_message_body_like_content(): void
    {
        $payload = "header text\n<feedback><report_metadata><report_id>inline-1</report_id></report_metadata></feedback>";

        $payloads = app(DmarcAttachmentExtractor::class)->extractXmlPayloads([
            ['name' => 'inline-body.xml', 'content_type' => 'text/plain', 'content' => $payload],
        ]);

        $this->assertCount(1, $payloads);
        $this->assertStringContainsString('<feedback>', $payloads[0]);
    }
}

