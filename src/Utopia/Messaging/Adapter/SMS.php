<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;

abstract class SMS extends Adapter
{
    public function getType(): string
    {
        return 'sms';
    }

    public function getMessageType(): string
    {
        return SMSMessage::class;
    }

    /**
     * Send an SMS message.
     *
     * @param  SMSMessage  $message Message to send.
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception If the message fails.
     */
    abstract protected function process(SMSMessage $message): array;
}
