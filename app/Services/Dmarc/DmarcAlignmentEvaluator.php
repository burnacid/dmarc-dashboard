<?php

namespace App\Services\Dmarc;

class DmarcAlignmentEvaluator
{
    public function __construct(
        private readonly OrganizationalDomainResolver $organizationalDomainResolver,
    ) {}

    public function isAligned(?string $headerFromDomain, ?string $authDomain, ?string $mode): ?bool
    {
        $headerFromDomain = $this->normalize($headerFromDomain);
        $authDomain = $this->normalize($authDomain);

        if ($headerFromDomain === null || $authDomain === null) {
            return null;
        }

        if ($headerFromDomain === $authDomain) {
            return true;
        }

        if ($mode === 's') {
            return false;
        }

        $headerFromOrgDomain = $this->organizationalDomainResolver->resolve($headerFromDomain);
        $authOrgDomain = $this->organizationalDomainResolver->resolve($authDomain);

        if ($headerFromOrgDomain === null || $authOrgDomain === null) {
            return false;
        }

        return $headerFromOrgDomain === $authOrgDomain;
    }

    private function normalize(?string $domain): ?string
    {
        $domain = strtolower(trim((string) $domain));

        return $domain === '' ? null : $domain;
    }
}
