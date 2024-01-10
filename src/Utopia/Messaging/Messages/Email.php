<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class Email implements Message
{
    /**
     * @param  array<string>  $to The recipients of the email.
     * @param  string  $subject The subject of the email.
     * @param  string  $content The content of the email.
     * @param  string  $fromName The name of the sender.
     * @param  string  $fromEmail The email address of the sender.
     * @param  array<array<string,string>>|null  $cc . The CC recipients of the email. Each recipient should be an array containing a "name" and an "email" key.
     * @param  array<array<string,string>>|null  $bcc . The BCC recipients of the email. Each recipient should be an array containing a "name" and an "email" key.
     * @param  string|null  $replyToName The name of the reply to.
     * @param  string|null  $replyToEmail The email address of the reply to.
     * @param  array<string, mixed>|null  $attachments The attachments of the email.
     * @param  bool  $html Whether the message is HTML or not.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        private array $to,
        private string $subject,
        private string $content,
        private string $fromName,
        private string $fromEmail,
        private ?string $replyToName = null,
        private ?string $replyToEmail = null,
        private ?array $cc = null,
        private ?array $bcc = null,
        private ?array $attachments = null,
        private bool $html = false
    ) {
        if (\is_null($this->replyToName)) {
            $this->replyToName = $this->fromName;
        }

        if (\is_null($this->replyToEmail)) {
            $this->replyToEmail = $this->fromEmail;
        }

        if (!\is_null($this->cc)) {
            foreach ($this->cc as $recipient) {
                if (!isset($recipient['name']) || !isset($recipient['email'])) {
                    throw new \InvalidArgumentException('Each recipient in cc must have a name and email');
                }
            }
        }

        if (!\is_null($this->bcc)) {
            foreach ($this->bcc as $recipient) {
                if (!isset($recipient['name']) || !isset($recipient['email'])) {
                    throw new \InvalidArgumentException('Each recipient in bcc must have a name and email');
                }
            }
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

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function getReplyToName(): string
    {
        return $this->replyToName;
    }

    public function getReplyToEmail(): string
    {
        return $this->replyToEmail;
    }

    /**
     * @return array<array<string, string>>|null
     */
    public function getCC(): ?array
    {
        return $this->cc;
    }

    /**
     * @return array<array<string, string>>|null
     */
    public function getBCC(): ?array
    {
        return $this->bcc;
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
