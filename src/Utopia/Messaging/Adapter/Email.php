<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\Email as EmailMessage;

abstract class Email extends Adapter
{
    public function getType(): string
    {
        return 'email';
    }

    public function getMessageType(): string
    {
        return EmailMessage::class;
    }

    /**
     * Process an email message.
     *
     * @param EmailMessage $message
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception
     */
    abstract protected function process(EmailMessage $message): array;
}
