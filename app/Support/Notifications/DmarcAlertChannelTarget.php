<?php

namespace App\Support\Notifications;

class DmarcAlertChannelTarget
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly string $type,
        public readonly array $config = [],
    ) {
    }

    public function isMail(): bool
    {
        return $this->type === 'email';
    }

    public function isNtfy(): bool
    {
        return $this->type === 'ntfy';
    }
}

