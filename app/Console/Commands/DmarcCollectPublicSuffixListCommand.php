<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('dmarc:collect-public-suffix-list')]
#[Description('Download the latest public suffix list used for DMARC alignment resolution')]
class DmarcCollectPublicSuffixListCommand extends Command
{
    private const SOURCE_URL = 'https://publicsuffix.org/list/public_suffix_list.dat';

    public function handle(): int
    {
        $response = Http::timeout(30)->get(self::SOURCE_URL);

        if (! $response->successful() || trim($response->body()) === '') {
            $this->error('Failed to download the public suffix list.');

            return self::FAILURE;
        }

        $path = storage_path('app/dns/public_suffix_list.dat');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $response->body());

        $this->info(sprintf('Public suffix list updated (%d bytes).', strlen($response->body())));

        return self::SUCCESS;
    }
}
