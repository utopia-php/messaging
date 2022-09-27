<?php

namespace Utopia\Messaging\SMS;

use Utopia\Messaging\Message;

class SMSMessage implements Message
{
    public function __construct(
        private array $to,
        private string $content,
        private ?string $from = null,
        private ?array $attachments = null,
    ) {
    }

    /**
     * @return array
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @return array|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }
}
