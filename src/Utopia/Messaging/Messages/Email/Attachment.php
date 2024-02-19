<?php

namespace Utopia\Messaging\Messages\Email;

class Attachment
{
    public function __construct(
        private string $name,
        private string $content,
        private string $type,
        private string $disposition = 'attachment',
        private string $encoding = 'base64'
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDisposition(): string
    {
        return $this->disposition;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }
}
