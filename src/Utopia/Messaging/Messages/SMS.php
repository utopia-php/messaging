<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class SMS implements Message
{
    private ?string $origin = null;

    /**
     * @param  array<string>  $to
     * @param  array<string>|null  $attachments
     */
    public function __construct(
        private array $to,
        private string $content,
        private ?string $from = null,
        private ?array $attachments = null,
    ) {
    }

    /**
     * @return array<string>
     */
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

    /**
     * @return array<string>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setOrigin(?string $origin): self
    {
        $this->origin = $origin;

        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }
}
