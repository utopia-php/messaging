<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;
use Utopia\Messaging\Messages\Email\Attachment;

class Email implements Message
{
    private ?string $origin = null;

    /**
     * @var array<array<string,string>>
     */
    private array $to;

    /**
     * @var array<array<string,string>>|null
     */
    private ?array $cc;

    /**
     * @var array<array<string,string>>|null
     */
    private ?array $bcc;

    /**
     * @param  array<string|array<string,string>>  $to The recipients of the email. Each entry can be an email string or an associative array with 'email' and optional 'name' keys.
     * @param  string  $subject The subject of the email.
     * @param  string  $content The content of the email.
     * @param  string  $fromName The name of the sender.
     * @param  string  $fromEmail The email address of the sender.
     * @param  string|null  $replyToName The name of the reply to.
     * @param  string|null  $replyToEmail The email address of the reply to.
     * @param  array<string|array<string,string>>|null  $cc The CC recipients of the email. Same format as $to.
     * @param  array<string|array<string,string>>|null  $bcc The BCC recipients of the email. Same format as $to.
     * @param  array<Attachment>|null  $attachments The attachments of the email.
     * @param  bool  $html Whether the message is HTML or not.
     */
    public function __construct(
        array $to,
        private string $subject,
        private string $content,
        private string $fromName,
        private string $fromEmail,
        private ?string $replyToName = null,
        private ?string $replyToEmail = null,
        ?array $cc = null,
        ?array $bcc = null,
        private ?array $attachments = null,
        private bool $html = false,
    ) {
        $this->to = \array_map(self::normalizeRecipient(...), $to);
        $this->cc = !\is_null($cc) ? \array_map(self::normalizeRecipient(...), $cc) : null;
        $this->bcc = !\is_null($bcc) ? \array_map(self::normalizeRecipient(...), $bcc) : null;

        if (\is_null($this->replyToName)) {
            $this->replyToName = $this->fromName;
        }

        if (\is_null($this->replyToEmail)) {
            $this->replyToEmail = $this->fromEmail;
        }
    }

    /**
     * Normalize a recipient entry to an associative array with 'email' and optional 'name' keys.
     *
     * @param  string|array<string,string>  $value
     * @return array<string,string>
     */
    private static function normalizeRecipient(string|array $value): array
    {
        if (\is_string($value)) {
            if ($value === '') {
                throw new \InvalidArgumentException('Recipient email must not be empty.');
            }

            return ['email' => $value];
        }

        if (!isset($value['email']) || $value['email'] === '') {
            throw new \InvalidArgumentException('Each recipient must have a non-empty "email" key.');
        }

        return $value;
    }

    /**
     * @return array<array<string,string>>
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
     * @return array<array<string,string>>|null
     */
    public function getCC(): ?array
    {
        return $this->cc;
    }

    /**
     * @return array<array<string,string>>|null
     */
    public function getBCC(): ?array
    {
        return $this->bcc;
    }

    /**
     * @return array<Attachment>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function isHtml(): bool
    {
        return $this->html;
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
