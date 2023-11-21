<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class SMS implements Message
{
    public function __construct(
        private array $to,
        private string $content,
        private ?string $from = null,
        private ?array $attachments = null,
    ) {
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }
}
