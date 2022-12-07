<?php

namespace Utopia\Messaging\Messages;

use Utopia\Messaging\Message;

class Discord implements Message
{
    public function __construct(
        private string $content,
        private ?string $username = null,
        private ?string $avatarUrl = null,
    )
    {
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
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }
}