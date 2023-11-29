<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class Email implements Message
{
    /**
     * @param  array<string>  $to The recipients of the email.
     * @param  string  $subject The subject of the email.
     * @param  string  $content The content of the email.
     * @param  string  $from The name of sender.
     * @param  string  $senderEmailAddress The email address of sender.
     * @param  string|null  $ccName The CC Name of the email.
     * @param  string|null  $ccEmail The CC Email of the email.
     * @param  string|null  $bccName The BCC Name of the email.
     * @param  string|null  $bccEmail The BCC Email of the email.
     * @param  string  $replyTo The reply to of the email.
     * @param  array<string, mixed>|null  $attachments The attachments of the email.
     * @param  bool  $html Whether the message is HTML or not.
     */
    public function __construct(
        private array $to,
        private string $subject,
        private string $content,
        private string $from,
        private string $senderEmailAddress,
        private ?string $replyTo = null,
        private ?string $ccName = null,
        private ?string $ccEmail = null,
        private ?string $bccName = null,
        private ?string $bccEmail = null,
        private ?array $attachments = null,
        private bool $html = false
    ) {
        if (\is_null($this->replyTo)) {
            $this->replyTo = $this->senderEmailAddress;
        }
    }

    /**
     * @return array<string>
     */
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

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getSenderEmailAddress(): string
    {
        return $this->senderEmailAddress;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    public function getCcName(): ?string
    {
        return $this->ccName;
    }

    public function getCcEmail(): ?string
    {
        return $this->ccEmail;
    }

    public function getBccName(): ?string
    {
        return $this->bccName;
    }

    public function getBccEmail(): ?string
    {
        return $this->bccEmail;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function isHtml(): bool
    {
        return $this->html;
    }
}
