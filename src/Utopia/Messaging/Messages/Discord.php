<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class Discord implements Message
{
    /**
     * @param  array<string, mixed>|null  $embeds
     * @param  array<string, mixed>|null  $allowedMentions
     * @param  array<string, mixed>|null  $components
     * @param  array<string, mixed>|null  $attachments
     */
    public function __construct(
        private string $content,
        private ?string $username = null,
        private ?string $avatarUrl = null,
        private ?bool $tts = null,
        private ?array $embeds = null,
        private ?array $allowedMentions = null,
        private ?array $components = null,
        private ?array $attachments = null,
        private ?string $flags = null,
        private ?string $threadName = null,
        private ?bool $wait = null,
        private ?string $threadId = null
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getTts(): ?bool
    {
        return $this->tts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEmbeds(): ?array
    {
        return $this->embeds;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAllowedMentions(): ?array
    {
        return $this->allowedMentions;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getComponents(): ?array
    {
        return $this->components;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function getFlags(): ?string
    {
        return $this->flags;
    }

    public function getThreadName(): ?string
    {
        return $this->threadName;
    }

    public function getWait(): ?bool
    {
        return $this->wait;
    }

    public function getThreadId(): ?string
    {
        return $this->threadId;
    }
}
