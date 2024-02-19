<?php

namespace Utopia\Messaging\Messages\Email;

class Attachment
{
    /**
     * @param string $name  The name of the file.
     * @param string $path  The content of the file.
     * @param string $type  The MIME type of the file.
     */
    public function __construct(
        private string $name,
        private string $path,
        private string $type,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
