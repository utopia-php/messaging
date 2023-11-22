<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class Email implements Message
{
    /**
     * @param  array  $to The recipients of the email.
     * @param  string  $subject The subject of the email.
     * @param  string  $content The content of the email.
     * @param  string|null  $from The sender of the email.
     * @param  array|null  $attachments The attachments of the email.
     * @param  bool  $html Whether the message is HTML or not.
     */
    public function __construct(
        private array $to,
        private string $subject,
        private string $content,
        private ?string $from = null,
        private ?array $attachments = null,
        private bool $html = false
    ) {
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
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

    public function isHtml(): bool
    {
        return $this->html;
    }
}
