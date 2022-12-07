<?php

namespace Utopia\Messaging\Adapters;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Message;
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
     * @param EmailMessage $message Message to process.
     * @return string The response body.
     */
    abstract protected function process(EmailMessage $message): string;
}
