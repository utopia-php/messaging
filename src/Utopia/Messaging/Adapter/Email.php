<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Email as EmailMessage;

abstract class Email extends Adapter
{
    protected const TYPE = 'email';
    protected const MESSAGE_TYPE = EmailMessage::class;

    protected const MAX_ATTACHMENT_BYTES = 25 * 1024 * 1024;

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getMessageType(): string
    {
        return static::MESSAGE_TYPE;
    }

    /**
     * Process an email message.
     *
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception
     */
    abstract protected function process(EmailMessage $message): array;
}
