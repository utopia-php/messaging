<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class SMS implements Message
{
    /**
     * @param  array<string>  $to The recipients of the push notification.
     * @param  array<string>  $attachments The recipients of the push notification.
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
     * @return array<string>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }
}
