<?php

namespace App\Notifications\Channels;

class NtfyMessage
{
    public string $title = '';

    public string $body = '';

    /** @var int 1 (min) to 5 (max) */
    public int $priority = 3;

    /** @var array<int, string> */
    public array $tags = [];

    public string $clickUrl = '';

    public static function create(string $body = ''): static
    {
        $msg = new static;
        $msg->body = $body;

        return $msg;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function priority(int $priority): static
    {
        $this->priority = max(1, min(5, $priority));

        return $this;
    }

    /** @param array<int, string> $tags */
    public function tags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function clickUrl(string $url): static
    {
        $this->clickUrl = $url;

        return $this;
    }
}

