<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Email as EmailMessage;

abstract class Email extends Adapter
{
    private const TYPE = 'email';
    private const MESSAGE_TYPE = EmailMessage::class;

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessageType(): string
    {
        return self::MESSAGE_TYPE;
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
