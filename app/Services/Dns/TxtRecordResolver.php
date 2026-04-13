<?php

namespace App\Services\Dns;

interface TxtRecordResolver
{
    /**
     * @return list<string>
     */
    public function resolveTxtRecords(string $host): array;
}

