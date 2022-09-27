<?php

namespace Utopia\Messaging\Email;

use Utopia\Messaging\Message;

class EmailMessage implements Message
{
    /**
     * @param array $to The recipients of the email.
     * @param string $subject The subject of the email.
     * @param string $content The content of the email.
     * @param string|null $from The sender of the email.
     * @param array|null $attachments The attachments of the email.
     * @param bool $html Whether the message is HTML or not.
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
    public function getSubject(): string
    {
        return $this->subject;
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

    /**
     * @return bool
     */
    public function isHtml(): bool
    {
        return $this->html;
    }
}
