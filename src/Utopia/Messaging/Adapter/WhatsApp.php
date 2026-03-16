<?php

namespace Utopia\Messaging\Adapter;

use Utopia\Messaging\Adapter;
use Utopia\Messaging\Messages\SMS as SMSMessage;

abstract class WhatsApp extends Adapter
{
    protected const TYPE = 'whatsapp';
    protected const MESSAGE_TYPE = SMSMessage::class;

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getMessageType(): string
    {
        return static::MESSAGE_TYPE;
    }

    /**
     * Send a WhatsApp message.
     *
     * @param  SMSMessage  $message Message to send.
     * @return array{deliveredTo: int, type: string, results: array<array<string, mixed>>}
     *
     * @throws \Exception If the message fails.
     */
    abstract protected function process(SMSMessage $message): array;
}
