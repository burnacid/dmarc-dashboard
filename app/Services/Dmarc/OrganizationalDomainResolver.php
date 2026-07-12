<?php

namespace App\Services\Dmarc;

use Pdp\Rules;
use Throwable;

class OrganizationalDomainResolver
{
    private const DATA_PATH = 'app/dns/public_suffix_list.dat';

    private ?Rules $rules = null;

    private bool $rulesLoadFailed = false;

    public function resolve(?string $domain): ?string
    {
        $domain = trim((string) $domain);

        if ($domain === '') {
            return null;
        }

        $rules = $this->rules();

        if (! $rules) {
            return null;
        }

        try {
            return $rules->resolve($domain)->registrableDomain()->toString();
        } catch (Throwable) {
            return null;
        }
    }

    private function rules(): ?Rules
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        if ($this->rulesLoadFailed) {
            return null;
        }

        $path = storage_path(self::DATA_PATH);

        if (! is_file($path)) {
            $this->rulesLoadFailed = true;

            return null;
        }

        try {
            $this->rules = Rules::fromPath($path);
        } catch (Throwable) {
            $this->rulesLoadFailed = true;

            return null;
        }

        return $this->rules;
    }
}
